
CKEDITOR.plugins.add( 'readmore', {
	requires: ['fakeobjects'],
	icons: 'readmore', // %REMOVE_LINE_CORE%
	hidpi: true, // %REMOVE_LINE_CORE%

	init: function(editor)
	{
		// Register the command.
		editor.addCommand('readmore',
		{
			exec: function()
			{
				// We only want to allow for one "Read More" element per field type.
				var images = editor.document.getElementsByTag('img');

				for (var i = 0, len = images.count(); i < len; i++)
				{
					var img = images.getItem(i);

					if (img.hasClass('cke_wygwam_read_more'))
					{
						if (confirm('This entry already contains a "Read More" break.  Would you like to remove it and place a new one here?'))
						{
							img.remove();
							break;
						}
						else
							return;
					}
				}

				insertComment('read_more');
			}
		});

		// Register the toolbar button.
		editor.ui.addButton('ReadMore',
		{
			label: 'Read More',
			command: 'readmore'
		});

		// Inserts an HTML comment into the editor
		function insertComment(text)
		{
			if (!CKEDITOR.dom.comment.prototype.getAttribute )
			{
				CKEDITOR.dom.comment.prototype.getAttribute = function() { return ''; };
				CKEDITOR.dom.comment.prototype.attributes = { align : '' };
			}

			// Create the fake element that will be inserted into the document.
			// Declare it as an <hr> so it will behave like a block element
			var fakeElement = editor.createFakeElement(new CKEDITOR.dom.comment(text), 'cke_wygwam_' + text, 'hr');


			// Can't use editor.insertElement() because we need comment at <body> level, so we use range manipulation.

			// Get a DOM range from the current selection.
			var range = editor.getSelection().getRanges()[0],
			    elementsPath = new CKEDITOR.dom.elementPath(range.getCommonAncestor(true)),
			    element = (elementsPath.block && elementsPath.block.getParent()) || elementsPath.blockLimit,
			    hasMoved;

			// If we're not in <body> move the position to after the elements until reaching <body>.
			// This may happen when inside tables, lists, blockquotes, etc.
			while (element && element.getName() != 'body')
			{
				range.moveToPosition(element, CKEDITOR.POSITION_AFTER_END);
				hasMoved = 1;
				element = element.getParent();
			}

			// Split the current block.
			if (!hasMoved)
				range.splitBlock('p');

			// Insert the fake element into the document.
			range.insertNode(fakeElement);

			// Now, we move the selection to the best possible place following our fake element.
			var next = fakeElement;

			while ((next = next.getNext()) && !range.moveToElementEditStart(next))
			{}
		}
	},

	afterInit: function(editor)
	{
		// Adds the comment processing rules to the data filter, so comments are replaced by fake elements.
		editor.dataProcessor.dataFilter.addRules(
		{
			comment: function(value)
			{
				if (!CKEDITOR.htmlParser.comment.prototype.getAttribute)
				{
					CKEDITOR.htmlParser.comment.prototype.getAttribute = function() { return ''; };
					CKEDITOR.htmlParser.comment.prototype.attributes = { align : '' };
				}

				if (value == 'read_more')
				{
					return editor.createFakeParserElement(new CKEDITOR.htmlParser.comment(value), 'cke_wygwam_' + value, 'hr');
				}

				return value;
			}
		});
	},

	onLoad : function()
	{
		// Add the style that renders our placeholder.
		CKEDITOR.addCss(
			'img.cke_wygwam_read_more' +
			'{' +
				'background-image: url(' + CKEDITOR.getUrl(this.path + 'images/readmore.png') + ');' +
				'background-position: left bottom;' +
				'background-repeat: no-repeat;' +
				'clear: both;' +
				'display: block;' +
				'float: none;' +
				'width:100% !important; _width:99.9% !important;' +
				'border-top: #CCC 1px dotted;' +
				'height: 9px !important;' +
			'}'
		);
    }
});
