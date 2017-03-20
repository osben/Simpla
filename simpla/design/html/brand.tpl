{* Вкладки *}
{capture name=tabs}
	{if in_array('products', $manager->permissions)}
		<li>
			<a href="index.php?module=ProductsAdmin">Товары</a>
		</li>
	{/if}
	{if in_array('categories', $manager->permissions)}
		<li>
			<a href="index.php?module=CategoriesAdmin">Категории</a>
		</li>
	{/if}
	<li class="active">
		<a href="index.php?module=BrandsAdmin">Бренды</a>
	</li>
	{if in_array('features', $manager->permissions)}
		<li>
			<a href="index.php?module=FeaturesAdmin">Свойства</a>
		</li>
	{/if}
{/capture}

{if $brand->id}
	{$meta_title = $brand->name scope=root}
{else}
	{$meta_title = 'Новый бренд' scope=root}
{/if}

{* Подключаем Tiny MCE *}
{include file='tinymce_init.tpl'}


{* On document load *}
{literal}
	<script>
		$(function () {

			// Удаление изображений
			$(".images a.delete").click(function () {
				$("input[name='delete_image']").val('1');
				$(this).closest("ul").fadeOut(200, function () {
					$(this).remove();
				});
				return false;
			});

			// Автозаполнение мета-тегов
			var meta_title_touched = true,
				meta_keywords_touched = true,
				meta_description_touched = true,
				url_touched = true;

			if ($('input[name="meta_title"]').val() == generate_meta_title() || $('input[name="meta_title"]').val() == '')
				meta_title_touched = false;
			if ($('input[name="meta_keywords"]').val() == generate_meta_keywords() || $('input[name="meta_keywords"]').val() == '')
				meta_keywords_touched = false;
			if ($('textarea[name="meta_description"]').val() == generate_meta_description() || $('textarea[name="meta_description"]').val() == '')
				meta_description_touched = false;
			if ($('input[name="url"]').val() == generate_url() || $('input[name="url"]').val() == '')
				url_touched = false;

			$('input[name="meta_title"]').change(function () {
				meta_title_touched = true;
			});
			$('input[name="meta_keywords"]').change(function () {
				meta_keywords_touched = true;
			});
			$('input[textarea="meta_description"]').change(function () {
				meta_description_touched = true;
			});
			$('input[name="url"]').change(function () {
				url_touched = true;
			});

			$('input[name="name"]').keyup(function () {
				set_meta();
			});

			function set_meta() {
				if (!meta_title_touched)
					$('input[name="meta_title"]').val(generate_meta_title());
				if (!meta_keywords_touched)
					$('input[name="meta_keywords"]').val(generate_meta_keywords());
				if (!meta_description_touched)
					$('textarea[name="meta_description"]').val(generate_meta_description());
				if (!url_touched)
					$('input[name="url"]').val(generate_url());
			}

			function generate_meta_title() {
				return $('input[name="name"]').val();
			}

			function generate_meta_keywords() {
				return $('input[name="name"]').val();
			}

			function generate_meta_description() {
				return $('input[name="name"]').val();
			}

			function generate_url() {
				var name = $('input[name="name"]').val();
				return Simpla.translit_url(name);
			}
		});

	</script>
{/literal}

{if $message_success}
	<!-- Системное сообщение -->
	<div class="message message_success">
		<span class="text">
			{if $message_error=='url_exists'}
				Бренд с таким адресом уже существует
			{elseif $message_error=='name_empty'}
				У бренда должно быть название
			{elseif $message_error=='url_empty'}
				URl адрес не может быть пустым
			{/if}
		</span>
		<a class="link" target="_blank" href="../brands/{$brand->url}">Открыть бренд на сайте</a>
		{if $smarty.get.return}
			<a class="button" href="{$smarty.get.return}">Вернуться</a>
		{/if}

		<span class="share">
			<a href="#" onClick='window.open("http://vkontakte.ru/share.php?url={$config->root_url|urlencode}/brands/{$brand->url|urlencode}&title={$brand->name|urlencode}&description={$brand->description|urlencode}&image={$config->root_url|urlencode}/files/brands/{$brand->image|urlencode}&noparse=true","displayWindow","width=700,height=400,left=250,top=170,status=no,toolbar=no,menubar=no");return false;'>
  				<img src="{$config->root_url}/simpla/design/images/vk_icon.png"/>
			</a>
			<a href="#" onClick='window.open("http://www.facebook.com/sharer.php?u={$config->root_url|urlencode}/brands/{$brand->url|urlencode}","displayWindow","width=700,height=400,left=250,top=170,status=no,toolbar=no,menubar=no");return false;'>
  				<img src="{$config->root_url}/simpla/design/images/facebook_icon.png"/>
			</a>
			<a href="#" onClick='window.open("http://twitter.com/share?text={$brand->name|urlencode}&url={$config->root_url|urlencode}/brands/{$brand->url|urlencode}&hashtags={$brand->meta_keywords|replace:' ':''|urlencode}","displayWindow","width=700,height=400,left=250,top=170,status=no,toolbar=no,menubar=no");return false;'>
  				<img src="{$config->root_url}/simpla/design/images/twitter_icon.png"/>
			</a>
		</span>

	</div>
	<!-- Системное сообщение (The End)-->
{/if}

{if $message_error}
	<!-- Системное сообщение -->
	<div class="message message_error">
		<span class="text">
			{if $message_error=='url_exists'}
				Бренд с таким адресом уже существует
			{else}
				{$message_error}
			{/if}
		</span>
		{if $smarty.get.return}
			<a class="button" href="{$smarty.get.return}">Вернуться</a>
		{/if}
	</div>
	<!-- Системное сообщение (The End)-->
{/if}


<!-- Основная форма -->
<form method="post" id="product" enctype="multipart/form-data">
	<input type="hidden" name="session_id" value="{$smarty.session.id}">

	<div id="name">
		<input placeholder="" class="name" name=name type="text" value="{$brand->name|escape}"/>
		<input name="id" type="hidden" value="{$brand->id|escape}"/>
	</div>

	<!-- Левая колонка свойств товара -->
	<div id="column_left">

		<!-- Параметры страницы -->
		<div class="block layer">
			<h2>Параметры страницы</h2>
			<ul>
				<li>
					<label for="url" class="property">Адрес</label>
					<div class="page_url"> /brands/</div>
					<input id="url" name="url" class="page_url" type="text" value="{$brand->url|escape}"/>
				</li>
				<li>
					<label for="meta_title" class="property">Заголовок</label>
					<input id="meta_title" name="meta_title" class="simpla_inp" type="text" value="{$brand->meta_title|escape}"/>
				</li>
				<li>
					<label for="meta_keywords" class="property">Ключевые слова</label>
					<input id="meta_keywords" name="meta_keywords" class="simpla_inp" type="text" value="{$brand->meta_keywords|escape}"/>
				</li>
				<li>
					<label for="meta_description" class="property">Описание</label>
					<textarea id="meta_description" name="meta_description" class="simpla_inp">
						{$brand->meta_description|escape}
					</textarea>
				</li>
			</ul>
		</div>
		<!-- Параметры страницы (The End)-->

		<input class="button_green button_save" type="submit" name="" value="Сохранить"/>
	</div>
	<!-- Левая колонка свойств товара (The End)-->

	<!-- Правая колонка свойств товара -->
	<div id="column_right">

		<!-- Изображение -->
		<div class="block layer images">
			<h2>Изображение бренда</h2>
			<input class="upload_image" name="image" type="file" accept="image/*">
			<input type="hidden" name="delete_image" value="">
			{if $brand->image}
				<ul>
					<li>
						<a href="#" class="delete">
							<img src="design/images/cross-circle-frame.png">
						</a>
						<img src="../{$config->brands_images_dir}{$brand->image}" alt=""/>
					</li>
				</ul>
			{/if}
		</div>

	</div>
	<!-- Правая колонка свойств товара (The End)-->

	<!-- Описагние бренда -->
	<div class="block layer">
		<h2>Описание</h2>
		<textarea id="description" name="description" class="editor_large">{$brand->description|escape}</textarea>
	</div>
	<!-- Описание бренда (The End)-->
	<input class="button_green button_save" type="submit" name="save" value="Сохранить"/>

</form>
<!-- Основная форма (The End) -->

