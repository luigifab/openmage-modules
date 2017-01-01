/**
 * Copyright 2012-2017 | Fabrice Creuzot (luigifab) <code~luigifab~info>
 * Created D/28/02/2016, updated S/19/11/2016
 * https://redmine.luigifab.info/projects/magento/wiki/modules
 *
 * This program is free software, you can redistribute it or modify
 * it under the terms of the GNU General Public License (GPL).
 */

// dépend de Prototype
var modules = {

	start: function () {

		if (!document.querySelector('body[class*="adminhtml-modules-index-index"]'))
			return;

		console.info('modules.app hello!');

		var elems = document.querySelectorAll('table.data tr.filter th'), elem, search, id;
		for (elem in elems) if (elems.hasOwnProperty(elem) && !isNaN(elem)) {

			id = elems[elem].parentNode.parentNode.parentNode.getAttribute('id');

			if (elems[elem].childNodes.length > 0)
				elems[elem].removeChild(elems[elem].firstChild);

			if (elems[elem].getAttribute('class').indexOf('last') > 0)
				continue;

			search = document.createElement('input');
			search.setAttribute('type', 'search');
			search.setAttribute('class', 'input-text');
			search.setAttribute('onkeyup', "modules.filter('" + id + "');");
			elems[elem].appendChild(search);
		}

		if (sessionStorage && sessionStorage.getItem('modules_search')) {
			search = document.querySelector('div.content-header input[type="search"]');
			search.value = sessionStorage.getItem('modules_search');
			modules.filter(search);
		}
	},

	reset: function () {

		var elems = document.querySelectorAll('table.data input[type="search"]'), elem;
		for (elem in elems) if (elems.hasOwnProperty(elem) && !isNaN(elem))
			elems[elem].value = '';

		elems = document.querySelectorAll('table.data tbody tr[style]');
		for (elem in elems) if (elems.hasOwnProperty(elem) && !isNaN(elem))
			elems[elem].removeAttribute('style');

		document.querySelector('div.content-header input[type="search"]').value = '';
		document.querySelector('div.content-header-floating input[type="search"]').value = '';

		if (sessionStorage)
			sessionStorage.removeItem('modules_search');
	},

	filter: function (data) {

		if (typeof data !== 'string') {

			var elems = document.querySelectorAll('table.data'), elem, search = data.value;
			for (elem in elems) if (elems.hasOwnProperty(elem) && !isNaN(elem)) {
				elems[elem].querySelector('input[type="search"]').value = search;
				this.action(elems[elem].getAttribute('id'));
			}

			elem = document.querySelector('div.content-header input[type="search"]');
			if (elem != data)
				elem.value = search;
			elem = document.querySelector('div.content-header-floating input[type="search"]');
			if (elem != data)
				elem.value = search;

			if (sessionStorage)
				sessionStorage.setItem('modules_search', search);
		}
		else if (document.getElementById(data)) {
			this.action(data);
		}
	},

	action: function (id) {

		var elems   = document.getElementById(id).querySelectorAll('tbody tr'), elem,
		    searchs = document.getElementById(id).querySelectorAll('input[type="search"]'), search, row,
		    words, word, i,
		    cell, show;

		// pour chaque ligne
		for (elem in elems) if (elems.hasOwnProperty(elem) && !isNaN(elem)) {

			row = 0;
			show = [];

			// pour chaque colonne (car toutes les colonnes peuvent avoir un filtre actif)
			// words = ce qu'on cherche dans la colonne courante
			// cell  = ce qu'il y a dans la cellule de la colonne de la ligne courante (sans les balises HTML)
			for (search in searchs) if (searchs.hasOwnProperty(search) && !isNaN(search)) {

				words = searchs[search].value.toLowerCase().trim();
				cell  = elems[elem].querySelectorAll('td')[row++].innerHTML.replace(/(<([^>]+)>)/ig, '').toLowerCase().trim();

				if (words.length > 0) {
					// si la recherche se fait avec plusieurs mots
					// pour que la recherche soit valide, on doit trouver tous les mots dans la celulle
					if (words.indexOf(' ') > -1) {
						words = words.split(' ');
						i = 0;
						for (word in words) if (words.hasOwnProperty(word) && !isNaN(word))
							i = (cell.indexOf(words[word]) > -1) ? i + 1 : i;
						show.push((i === words.length) ? true : false);
					}
					// si la recherche se fait avec un seul mot
					else {
						show.push((cell.indexOf(words) > -1) ? true : false);
					}
				}
				else {
					show.push(true);
				}
			}

			// maintenant que chaque colonne de la ligne a été vérifiée
			// si aucune colonne indique qu'il ne faut pas afficher la ligne, on affiche la ligne
			elems[elem].style.display = (show.indexOf(false) === -1) ? 'table-row' : 'none';
		}
	}
};

if (typeof window.addEventListener === 'function')
	window.addEventListener('load', modules.start, false);