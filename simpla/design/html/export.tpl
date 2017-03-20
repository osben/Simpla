{capture name=tabs}
	{if in_array('import', $manager->permissions)}
		<li>
			<a href="index.php?module=ImportAdmin">Импорт</a>
		</li>
	{/if}
	<li class="active">
		<a href="index.php?module=ExportAdmin">Экспорт</a>
	</li>
	{if in_array('backup', $manager->permissions)}
		<li>
			<a href="index.php?module=BackupAdmin">Бекап</a>
		</li>
	{/if}
{/capture}

{$meta_title='Экспорт товаров' scope=root}

{if $message_error}
	<!-- Системное сообщение -->
	<div class="message message_error">
		<span class="text">
			{if $message_error == 'no_permission'}
				Установите права на запись в папку {$export_files_dir}
			{elseif $message_error == 'iconv_or_mb_convert_encoding'}
				Отсутствует iconv или mb_convert_encoding
			{else}
				{$message_error}
			{/if}
		</span>
	</div>
	<!-- Системное сообщение (The End)-->
{/if}


<div id="main_list">
	<h1>Экспорт товаров</h1>
	{if $message_error != 'no_permission'}
		<div id="progressbar"></div>
		<input class="button_green" id="start" type="button" name="" value="Экспортировать"/>
	{/if}
	{if $exports}
		<form id="list_form" method="post">
			<input type="hidden" name="session_id" value="{$smarty.session.id}">
			<div id="list">
				{foreach $exports as $export}
					<div class="row">
						{if $message_error != 'no_permission'}
							<div class="checkbox cell">
								<input title="выбрать" type="checkbox" name="check[]" value="{$export->name}"/>
							</div>
						{/if}
						<div class="name cell">
							<a href="files/export/{$export->name}">{$export->name}</a>
							({if $export->size>1024*1024}{($export->size/1024/1024)|round:2} МБ{else}{($export->size/1024)|round:2} КБ{/if}
							)
						</div>
						<div class="icons cell">
							{if $message_error != 'no_permission'}
								<a class="delete" title="Удалить" href="#"></a>
							{/if}
						</div>
						<div class="clear"></div>
					</div>
				{/foreach}
			</div>
			{if $message_error != 'no_permission'}
				<div id="action">
					<label id="check_all" class="dash_link">Выбрать все</label>

					<span id="select">
						<select title="Выбрать действие" name="action">
							<option value="delete">Удалить</option>
						</select>
					</span>

					<input id="apply_action" class="button_green" type="submit" value="Применить">
				</div>
			{/if}
		</form>
	{/if}
</div>
<script src="{$config->root_url}/simpla/design/js/piecon/piecon.js"></script>
<script>
	var filename = 'export_{trim($manager->login)}_{date("Y_m_d_G_i_s")}.csv';
	{literal}
	$(function () {

		// On document load
		$('input#start').click(function () {

			Piecon.setOptions({
				fallback: 'force'
			});
			Piecon.setProgress(0);
			$("#progressbar").progressbar({value: 0});

			$("#start, #list_form").hide('fast');
			do_export();

		});

		function do_export(page) {
			page = typeof(page) != 'undefined' ? page : 1;

			$.ajax({
				url: "ajax/export.php",
				data: {page: page, filename: filename},
				dataType: 'json',
				success: function (data) {
					if (data.error) {
						$("#progressbar").hide('fast');
						alert(data.error);
					}
					else if (data && !data.end) {
						Piecon.setProgress(Math.round(100 * data.page / data.totalpages));
						$("#progressbar").progressbar({value: 100 * data.page / data.totalpages});
						do_export(data.page * 1 + 1);
					}
					else {
						if (data && data.end) {
							Piecon.setProgress(100);
							$("#progressbar").hide('fast');
							window.location.href = 'files/export/' + filename;
						}
					}
				},
				error: function (xhr, status, errorThrown) {
					alert(errorThrown + '\n' + xhr.responseText);
				}

			});

		}

		// Раскраска строк
		$("#list div.row").colorize();

		// Выделить все
		$("#check_all").click(function () {
			$('#list input[type="checkbox"][name*="check"]').attr('checked', $('#list input[type="checkbox"][name*="check"]:not(:checked)').length > 0);
		});

		// Удалить
		$("a.delete").click(function () {
			$('#list input[type="checkbox"][name*="check"]').attr('checked', false);
			$(this).closest(".row").find('input[type="checkbox"][name*="check"]').attr('checked', true);
			$(this).closest("form").find('select[name="action"] option[value=delete]').attr('selected', true);
			$(this).closest("form").submit();
		});

		$("form#list_form").submit(function () {
			if ($('select[name="action"]').val() == 'delete' && !confirm('Подтвердите удаление'))
				return false;
		});
	});
	{/literal}
</script>

<style>
	#list {
		margin-top: 20px;
	}

	.ui-progressbar-value {
		background-image: url(design/images/progress.gif);
		background-position: left;
		border-color: #009ae2;
	}

	#progressbar {
		clear: both;
		height: 29px;
	}

	#result {
		clear: both;
		width: 100%;
	}

	#download {
		display: none;
		clear: both;
	}
</style>
