/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	config.language = 'cs';
	//config.format_tags = 'p;h1;h2;h3;pre';
	config.removeDialogTabs = 'image:advanced;link:advanced';

	//config.filebrowserBrowseUrl= 'js/cked/plugins/imagebrowser/browser/browser.html',
    //config.filebrowserUploadUrl= '/uploader/upload.php'
	
	config.toolbar = [
	{ name: 'document', groups: [ 'mode'], items: [ 'Source'] },
	{ name: 'clipboard', groups: [ 'undo' ], items: ['Undo', 'Redo'] },
	{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Underline', 'Strike', '-', 'RemoveFormat' ] },
	{ name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ], items: [ 'BulletedList','Blockquote', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
	{ name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
	{ name: 'insert', items: [ 'Image', 'HorizontalRule' ] },
	{ name: 'tools', items: [ 'Maximize', 'ShowBlocks' ] },
	'/',
	{ name: 'styles', items: [ 'Styles', 'Format', 'FontSize' ] },
	{ name: 'colors', items: [ 'TextColor' ] }
	,
	
];
	config.allowedContent = true;
	config.autoGrow_onStartup = true;
	config.extraPlugins = 'autogrow';
	//config.extraAllowedContent = 'a[href,document-href]';
};
