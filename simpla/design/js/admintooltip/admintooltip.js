(function($){
	'use strict';

	$('<a href="simpla/" class="admin-bookmark"></a>').appendTo('body');
	
	var $tooltip = $('<div>', { class: 'admin-tooltip', html: $('<div>', {class: 'admin-tooltip-inner'}) }).appendTo('body');		
	var tooltipCanClose = false;
	
	$(document)
		.on('mouseover',  '[data-page]', 		show_tooltip)
		.on('mouseover',  '[data-category]',	show_tooltip)
		.on('mouseover',  '[data-brand]',		show_tooltip)
		.on('mouseover',  '[data-product]', 	show_tooltip)
		.on('mouseover',  '[data-post]', 		show_tooltip)
		.on('mouseover',  '[data-feature]', 	show_tooltip);	

	function show_tooltip()
	{
		var $link = $(this);
		
		$tooltip.addClass('admin-tooltip-visible');
		
		$link.on('mouseleave', function(){
			$tooltip.removeClass('admin-tooltip-visible');
		});

		var flip = $link.offset().left + $tooltip.width() + 25 > $('body').width();

		$tooltip
			.css({
				'top':  $link.height() + $link.offset().top + 'px',
				'left': $link.offset().left - (flip ? $tooltip.width() - $link.outerWidth(true) : 0) + 'px'
			})
			.removeClass('flipped')
			.addClass(flip ? 'flipped' : '');

		var urlFrom = encodeURIComponent(window.location);
		var tooltipContent = '';
		
		var id = '';
		
		if(id = $link.attr('data-page'))
			tooltipContent = createTooltipContent('PageAdmin', id, urlFrom);
		else if(id = $link.attr('data-category'))
			tooltipContent = createTooltipContent('CategoryAdmin', id, urlFrom);
		else if(id = $link.attr('data-brand'))
			tooltipContent = createTooltipContent('BrandAdmin', id, urlFrom);
		else if(id = $link.attr('data-product'))
			tooltipContent = createTooltipContent('ProductAdmin', id, urlFrom);
		else if(id = $link.attr('data-post'))
			tooltipContent = createTooltipContent('PostAdmin', id, urlFrom);
		else if(id = $link.attr('data-feature'))
			tooltipContent = createTooltipContent('FeatureAdmin', id, urlFrom);
		
		$tooltip.find('.admin-tooltip-inner').html( tooltipContent );
	}

	function createTooltipContent(module, id, urlFrom){
		var $linkEdit = $('<a>', {
			href: 'simpla/index.php?module=' + module + '&id=' + id + '&return=' + urlFrom,
			class: 'admin-tooltip-edit',
			text: 'Редактировать'
		});
		
		if(module == 'PageAdmin' || module == 'CategoryAdmin')
		{
			var $linkAdd = $('<a>', {
				href: 'simpla/index.php?module=' + (module == 'CategoryAdmin' ? 'ProductAdmin&category_id=' + id : module) + '&return=' + urlFrom,
				class: 'admin-tooltip-add',
				text: module == 'PageAdmin' ? 'Добавить страницу' : 'Добавить товар'
			});
			
			return [$linkEdit, $linkAdd];
		}

		return $linkEdit;
	}
})(window.jQuery);