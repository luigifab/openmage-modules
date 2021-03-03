/**
 * Created D/28/02/2016
 * Updated V/21/02/2020
 *
 * Copyright 2012-2021 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://www.luigifab.fr/openmage/modules
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

if (window.NodeList && !NodeList.prototype.forEach) {
	NodeList.prototype.forEach = function (callback, that, i) {
		that = that || window;
		for (i = 0; i < this.length; i++)
			callback.call(that, this[i], i, this);
	};
}

var modules = new (function () {

	"use strict";

	this.start = function () {

		if (document.querySelector('body.adminhtml-modules-index-index')) {

			console.info('modules.app - hello');
			var search, data;

			// crée les inputs dans les cellules des entêtes
			// prend soin de supprimer ce qu'il y a dans les th
			// utilise l'id du tableau html
			document.querySelectorAll('table.data tr.filter th').forEach(function (elem) {

				while (elem.childNodes.length > 0)
					elem.removeChild(elem.firstChild);

				search = document.createElement('input');
				search.setAttribute('type', 'search');
				search.setAttribute('spellcheck', 'false');
				search.setAttribute('autocomplete', 'off');
				search.setAttribute('class', 'input-text');
				search.setAttribute('oninput', "modules.action('" + elem.parentNode.parentNode.parentNode.getAttribute('id') + "');");
				elem.appendChild(search);
			});

			// réutilise la recherche précédente
			data = this.storage('modules_search');
			if (data) {
				search = document.querySelector('div.content-header input[type="search"]');
				search.value = data;
				this.action(search);
			}
		}
	};

	this.filter = function (id) {

		var words, tmp, text, show, size, cnt;
		document.getElementById(id).querySelectorAll('tbody tr').forEach(function (line) {

			show = [];

			// pour chaque colonne (car toutes les colonnes peuvent avoir un filtre)
			// words = ce qu'on cherche dans la colonne courante
			// text  = ce qu'il y a dans la cellule de la colonne de la ligne courante
			document.getElementById(id).querySelectorAll('input[type="search"]').forEach(function (col, idx) {

				words = col.value.toLowerCase().trim(); // ce qu'on cherche

				// s'il y a des mots
				if (words.length > 0) {

					words = words.split(' ');
					size  = words.length;
					text  = line.querySelectorAll('td')[idx].innerHTML.replace(/<[^>]+>/ig, '').toLowerCase().trim(); // dans quoi on cherche
					cnt   = 0;

					words.forEach(function (word) {
						if ((word === '-') || (word === '|')) {
							size--;
						}
						else if (word.charAt(0) === '-') {
							size--;
							if (text.indexOf(word.substr(1)) > -1)
								size = -1;
						}
						else if (word.indexOf('|') > -1) {
							tmp = word.split('|');
							while (tmp.length > 0) {
								word = tmp.pop();
								if ((word.length > 0) && (text.indexOf(word) > -1)) {
									tmp = '';
									cnt++;
								}
							}
						}
						else if (text.indexOf(word) > -1) {
							cnt++;
						}
					});

					show.push(cnt === size);
				}
				else {
					show.push(true);
				}
			});

			// maintenant que chaque colonne de la ligne a été vérifiée
			// si aucune colonne indique qu'il ne faut pas afficher la ligne, on affiche la ligne
			line.setAttribute('style', (show.indexOf(false) > -1) ? 'display:none;' : '');
			line.removeAttribute('title');
		});
	};

	this.action = function (data) {

		// un objet = demande le filtrage de tous les tableaux
		if (typeof data != 'string') {

			if (data.altKey || data.ctrlKey || data.metaKey || data.shiftKey)
				return;

			document.querySelectorAll('table.data').forEach(function (elem) {
				elem.querySelector('input[type="search"]').value = data.value;
				this.filter(elem.getAttribute('id'));
			}, this); // pour que ci-dessus this = this

			this.storage('modules_search', data.value);
		}
		// un id = demande le filtrage du tableau
		else if (document.getElementById(data)) {
			this.filter(data);
		}
	};

	this.reset = function (elem) {

		this.storage('modules_search', null);

		// efface les filtres et les display
		document.querySelectorAll('table.data thead input[type="search"]').forEach(function (elem) { elem.value = ''; });
		document.querySelectorAll('table.data tbody tr[style]').forEach(function (elem) { elem.removeAttribute('style'); });

		// efface le filtre global
		elem = document.querySelector('div.content-header input[type="search"]');
		elem.value = '';
		elem.focus();
	};

	this.unload = function () {
		this.storage('modules_search', this.storage('modules_search'));
	};

	this.storage = function (key, value) {

		// remove
		if (value === null) {
			localStorage.removeItem(key);
			sessionStorage.removeItem(key);
		}
		// set
		else if (typeof value != 'undefined') {
			localStorage.setItem(key, value);
			sessionStorage.setItem(key, value);
		}
		// get
		else {
			return localStorage.getItem(key) || sessionStorage.getItem(key);
		}
	};

})();

if (typeof self.addEventListener == 'function') {
	self.addEventListener('load', modules.start.bind(modules));
	self.addEventListener('beforeunload', modules.unload.bind(modules));
}