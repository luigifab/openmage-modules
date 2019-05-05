/**
 * Created D/28/02/2016
 * Updated V/26/04/2019
 *
 * Copyright 2012-2019 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://www.luigifab.fr/magento/modules
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

		if (document.querySelector('body.adminhtml-modules-index-index')) {

			console.info('modules.app - hello');

			// crée les inputs avec un onkeyup dans les cellules des entêtes
			// prend soin de supprimer ce qu'il y a dans les th
			// utilise l'id du tableau html
			var elem, elems = document.querySelectorAll('table.data tr.filter th'), search, id, data;
			for (elem in elems) if (elems.hasOwnProperty(elem) && !isNaN(elem)) {

				id = elems[elem].parentNode.parentNode.parentNode.getAttribute('id');

				while (elems[elem].childNodes.length > 0)
					elems[elem].removeChild(elems[elem].firstChild);

				search = document.createElement('input');
				search.setAttribute('type', 'search');
				search.setAttribute('spellcheck', 'false');
				search.setAttribute('autocomplete', 'off');
				search.setAttribute('class', 'input-text');
				search.setAttribute('oninput', "modules.filter('" + id + "');");
				elems[elem].appendChild(search);
			}

			// réutilise la recherche précédente
			data = sessionStorage.getItem('modules_search');
			if (data) {
				search = document.querySelector('div.content-header input[type="search"]');
				search.value = data;
				modules.filter(search);
			}
		}
	},

	reset: function () {

		// efface les filtres
		var elem, elems = document.querySelectorAll('table.data input[type="search"]');
		for (elem in elems) if (elems.hasOwnProperty(elem) && !isNaN(elem))
			elems[elem].value = '';

		// efface les display
		elems = document.querySelectorAll('table.data tbody tr[style]');
		for (elem in elems) if (elems.hasOwnProperty(elem) && !isNaN(elem))
			elems[elem].removeAttribute('style');

		// efface le filtre global
		elem = document.querySelector('div.content-header input[type="search"]');
		elem.value = '';
		elem.focus();

		sessionStorage.removeItem('modules_search');
	},

	filter: function (data) {

		// un objet = demande le filtrage de tous les tableaux
		if (typeof data !== 'string') {

			if (data.altKey || data.ctrlKey || data.metaKey || data.shiftKey)
				return;

			var elem, elems = document.querySelectorAll('table.data'), search = data.value;
			for (elem in elems) if (elems.hasOwnProperty(elem) && !isNaN(elem)) {
				elems[elem].querySelector('input[type="search"]').value = search;
				this.action(elems[elem].getAttribute('id'));
			}

			sessionStorage.setItem('modules_search', search);
		}
		// un id = demande le filtrage du tableau
		else if (document.getElementById(data)) {
			this.action(data);
		}
	},

	action: function (id) {

		var line, lines = document.getElementById(id).querySelectorAll('tbody tr'),
		    col, cols = document.getElementById(id).querySelectorAll('input[type="search"]'),
		    word, words, i, text, show, size;

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
					size  = words.length;
					text  = lines[line].querySelectorAll('td')[col].innerHTML.replace(/<[^>]+>/ig, '').toLowerCase().trim();
					i     = 0;

					// si la recherche se fait avec plusieurs mots
					// pour que la recherche soit valide, on doit trouver tous les mots
					// sauf si un des mots comment par un -
					if (size > 1) {

						for (word in words) if (words.hasOwnProperty(word) && !isNaN(word)) {
							word = words[word];
							if (word === '-') {
								size -= 1;
							}
							else if (word[0] === '-') {
								if (text.indexOf(word.substr(1)) > -1) {
									size = -1;
									break;
								}
								size -= 1;
							}
							else {
								i = (text.indexOf(word) > -1) ? i + 1 : i;
							}
						}

						show.push((i === size) ? true : false);
					}
					// si la recherche se fait avec un seul mot
					else {
						if (words[0] === '-')
							show.push(true);
						else if (words[0][0] === '-')
							show.push((text.indexOf(words[0].substr(1)) > -1) ? false : true);
						else
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