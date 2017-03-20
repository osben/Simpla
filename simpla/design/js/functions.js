var Simpla = {};


// транслитерация урл
Simpla.translit_url = function (str) {
	str = str.toLowerCase();
	str = str.replace(/[\s]+/gi, '-');


	var ru = ("А-а-Б-б-В-в-Ґ-ґ-Г-г-Д-д-Е-е-Ё-ё-Є-є-Ж-ж-З-з-И-и-І-і-Ї-ї-Й-й-К-к-Л-л-М-м-Н-н-О-о-П-п-Р-р-С-с-Т-т-У-у-Ф-ф-Х-х-Ц-ц-Ч-ч-Ш-ш-Щ-щ-Ъ-ъ-Ы-ы-Ь-ь-Э-э-Ю-ю-Я-я").split("-");
	var en = ("A-a-B-b-V-v-G-g-G-g-D-d-E-e-E-e-E-e-ZH-zh-Z-z-I-i-I-i-I-i-J-j-K-k-L-l-M-m-N-n-O-o-P-p-R-r-S-s-T-t-U-u-F-f-H-h-TS-ts-CH-ch-SH-sh-SCH-sch-'-'-Y-y-'-'-E-e-YU-yu-YA-ya").split("-");
	var res = '';
	for (var i = 0, l = str.length; i < l; i++) {
		var s = str.charAt(i), n = ru.indexOf(s);
		if (n >= 0) {
			res += en[n];
		}
		else {
			res += s;
		}
	}
	res = res.replace(/[^0-9a-z_\-]+/gi, '');
	return res;
};

$.fn.colorize = function(){
	$(this).removeClass('even');
	$(this).filter(':even').addClass('even');
	return true;
};
