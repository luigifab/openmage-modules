/**
 * Created D/28/02/2016
 * Updated J/19/07/2018
 *
 * Copyright 2012-2018 | Fabrice Creuzot (luigifab) <code~luigifab~info>
 * https://www.luigifab.info/magento/modules
 *
 * This program is free software, you can redistribute it or modify
 * it under the terms of the GNU General Public License (GPL) as published
 * by the free software foundation, either version 2 of the license, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but without any warranty, without even the implied warranty of
 * merchantability or fitness for a particular purpose. See the
 * GNU General Public License (GPL) for more details.
 */

var modules = {

	start: function () {

		if (document.querySelector('body[class*="adminhtml-modules-index-index"]')) {

			console.info('modules.app - hello');

			// crée les inputs avec un onkeyup dans les cellules des entêtes
			// prend soin de supprimer ce qu'il y a dans les th
			// utilise l'id du tableau html
			var elems = document.querySelectorAll('table.data tr.filter th'), elem, search, id;
			for (elem in elems) if (elems.hasOwnProperty(elem) && !isNaN(elem)) {

				id = elems[elem].parentNode.parentNode.parentNode.getAttribute('id');

				while (elems[elem].childNodes.length > 0)
					elems[elem].removeChild(elems[elem].firstChild);

				//if (elems[elem].getAttribute('class').indexOf('last') < 0) {
					search = document.createElement('input');
					search.setAttribute('type', 'search');
					search.setAttribute('spellcheck', 'false');
					search.setAttribute('autocomplete', 'off');
					search.setAttribute('class', 'input-text');
					search.setAttribute('oninput', "modules.filter('" + id + "');");
					elems[elem].appendChild(search);
				//}
			}

			// réutilise la recherche précédente
			if (sessionStorage && sessionStorage.getItem('modules_search')) {
				search = document.querySelector('div.content-header input[type="search"]');
				search.value = sessionStorage.getItem('modules_search');
				modules.filter(search);
			}
		}
	},

	reset: function () {

		// efface les filtres
		var elems = document.querySelectorAll('table.data input[type="search"]'), elem;
		for (elem in elems) if (elems.hasOwnProperty(elem) && !isNaN(elem))
			elems[elem].value = '';

		// efface les display
		elems = document.querySelectorAll('table.data tbody tr[style]');
		for (elem in elems) if (elems.hasOwnProperty(elem) && !isNaN(elem))
			elems[elem].removeAttribute('style');

		// efface le filtre global
		document.querySelector('div.content-header input[type="search"]').value = '';
		document.querySelector('div.content-header input[type="search"]').focus();
		if (sessionStorage)
			sessionStorage.removeItem('modules_search');
	},

	filter: function (data) {

		// un objet = demande le filtrage de tous les tableaux
		if (typeof data !== 'string') {

			if (data.altKey || data.ctrlKey || data.metaKey || data.shiftKey)
				return;

			var elems = document.querySelectorAll('table.data'), elem, search = data.value;
			for (elem in elems) if (elems.hasOwnProperty(elem) && !isNaN(elem)) {
				elems[elem].querySelector('input[type="search"]').value = search;
				this.action(elems[elem].getAttribute('id'));
			}

			if (sessionStorage)
				sessionStorage.setItem('modules_search', search);
		}
		// un id = demande le filtrage du tableau
		else if (document.getElementById(data)) {
			this.action(data);
		}
	},

	action: function (id) {

		var lines = document.getElementById(id).querySelectorAll('tbody tr'), line,
		    cols  = document.getElementById(id).querySelectorAll('input[type="search"]'), col,
		    words, word, i,
		    text, show;

		for (line in lines) if (lines.hasOwnProperty(line) && !isNaN(line)) {

			show = [];

			// pour chaque colonne (car toutes les colonnes peuvent avoir un filtre)
			// words = ce qu'on cherche dans la colonne courante
			// text  = ce qu'il y a dans la cellule de la colonne de la ligne courante
			for (col in cols) if (cols.hasOwnProperty(col) && !isNaN(col)) {

				words = cols[col].value.toLowerCase().trim();

				// s'il y a des mots
				if (words.length > 0) {

					words = words.split(' ');
					text  = lines[line].querySelectorAll('td')[col].innerHTML.replace(/(<([^>]+)>)/ig, '').toLowerCase().trim();
					i     = 0;

					// si la recherche se fait avec plusieurs mots
					// pour que la recherche soit valide, on doit trouver tous les mots
					if (words.length > 1) {

						for (word in words) if (words.hasOwnProperty(word) && !isNaN(word))
							i = (text.indexOf(words[word]) > -1) ? i + 1 : i;

						show.push((i === words.length) ? true : false);
					}
					// si la recherche se fait avec un seul mot
					else {
						show.push((text.indexOf(words[0]) > -1) ? true : false);
					}
				}
				else {
					show.push(true);
				}
			}

			// maintenant que chaque colonne de la ligne a été vérifiée
			// si aucune colonne indique qu'il ne faut pas afficher la ligne, on affiche la ligne
			lines[line].setAttribute('style', (show.indexOf(false) === -1) ? '' : 'display:none;');
			lines[line].removeAttribute('title');
		}
	}
};

if (typeof self.addEventListener === 'function')
	self.addEventListener('load', modules.start, false);