/*

Copyright 2006-2008 Clipperz Srl

This file is part of Clipperz Community Edition.
Clipperz Community Edition is a web-based password manager and a
digital vault for confidential data.
For further information about its features and functionalities please
refer to http://www.clipperz.com

* Clipperz Community Edition is free software: you can redistribute
  it and/or modify it under the terms of the GNU Affero General Public
  License as published by the Free Software Foundation, either version
  3 of the License, or (at your option) any later version.

* Clipperz Community Edition is distributed in the hope that it will
  be useful, but WITHOUT ANY WARRANTY; without even the implied
  warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the GNU Affero General Public License for more details.

* You should have received a copy of the GNU General Public License
  along with Clipperz Community Edition.  If not, see
  <http://www.gnu.org/licenses/>.


*/



if (typeof(Clipperz) == 'undefined') { Clipperz = {}; }
if (typeof(Clipperz.PM) == 'undefined') { Clipperz.PM = {}; }
if (typeof(Clipperz.PM.Components) == 'undefined') { Clipperz.PM.Components = {}; }
if (typeof(Clipperz.PM.Components.Compact) == 'undefined') { Clipperz.PM.Components.Compact = {}; }

Clipperz.PM.Components.Compact.CompactHeader = function(anElement, args) {

    Clipperz.PM.Components.Compact.CompactHeader.superclass.constructor.call(this, anElement, args);

	this.render();
	
	return this;
};

YAHOO.extendX(Clipperz.PM.Components.Compact.CompactHeader, Clipperz.PM.Components.BaseComponent, {
	
	'toString': function() {
		return "Clipperz.PM.Components.Compact.CompactHeader";
	},

	//-----------------------------------------------------
	
	'render': function() {
		this.element().update("");

		YAHOO.ext.DomHelper.append(this.element().dom, {tag:'div', children:[
			{tag:'img', src:'./images/logo.gif'},
			{tag:'div', id:'lockBlock', children:[
				{tag:'input', type:'checkbox', id:'autolock'},
				{tag:'span', html:'auto'},
				{tag:'a', href:'#', html:Clipperz.PM.Strings['lockMenuLabel'], id:'lock'}
			]}
		]});
		
		YAHOO.ext.DomHelper.append(this.element().dom, 
			{tag:'div', id:'compactMiscLinks', children:[
				{tag:'a', id:'donateHeaderIconLink', target:'_blank', href:Clipperz.PM.Strings['donateHeaderLinkUrl'], children:[
					{tag:'img', id:'donateHeaderLinkIcon', src:'./images/smiles_small.gif'}
				]},
				{tag:'ul', children:[
					{tag:'li', children:[{tag:'a', id:'donateHeaderLink', html:'donate', target:'_blank'}]},
					{tag:'li', children:[{tag:'a', id:'creditsHeaderLink', html:'credits', target:'_blank'}]},
					{tag:'li', children:[{tag:'a', id:'feedbackHeaderLink', html:'feedback', target:'_blank'}]},
					{tag:'li', children:[{tag:'a', id:'helpHeaderLink', html:'help', target:'_blank'}]},
					{tag:'li', children:[{tag:'a', id:'forumHeaderLink', html:'forum', target:'_blank'}]}
				]}
			]}
		);

		YAHOO.ext.Element.get('lockBlock').setVisibilityMode(YAHOO.ext.Element.DISPLAY).hide();
		Clipperz.NotificationCenter.notify(this, 'switchLanguage');
	},

	//-----------------------------------------------------
	__syntaxFix__: '__syntaxFix__'
});

