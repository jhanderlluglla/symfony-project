"use strict";

var QuoteButton = function QuoteButton(context) {
  var ui = $.summernote.ui; // create button

  var button = ui.button({
    contents: '<b>"Q"</b>',
    tooltip: 'Quoted text',
    click: function click() {
      var text = context.invoke('editor.getSelectedText');
      var $node = $('<q>' + text + '</q>');
      var quotes = $(".note-editable q");
      var quote = quotes.filter(function (index, element) {
        return element.textContent === text;
      });

      if (quote.length) {
        quote.remove();
        context.invoke('editor.insertText', text);
      } else if (text !== "") {
        context.invoke('editor.insertNode', $node[0]);
      }
    }
  });
  return button.render(); // return button as jquery object
};

var BoldButton = function BoldButton(context) {
  var ui = $.summernote.ui;

  var button = ui.button({
    contents: '<i class="note-icon-bold"></i>',
    tooltip: 'Bold',
    click: function click() {
      var text = context.invoke('editor.getSelectedText');
      var $node = $('<strong>' + text + '</strong>');
      context.invoke('editor.insertNode', $node[0]);
    }
  });
  return button.render();
};

var ItalicButton = function ItalicButton(context) {
    var ui = $.summernote.ui;

    var button = ui.button({
        contents: '<i class="note-icon-italic"></i>',
        tooltip: 'Italic',
        click: function click() {
            var text = context.invoke('editor.getSelectedText');
            var $node = $('<em>' + text + '</em>');
            context.invoke('editor.insertNode', $node[0]);
        }
    });
    return button.render();
};

var PixabayButton = function PixabayButton(context) {
  var ui = $.summernote.ui;

  var button = ui.button({
    contents: '<div style="background-image: url(\'/img/pixabay.png\'); width: 16px; height: 16px; background-size: cover"></div>',
    tooltip: 'Pixabay',
    click: function click() {
        showPixabayModal(function (url) {
            var img = document.createElement('img');
            img.src = url;
            context.invoke('editor.insertNode', img);
        });
    }
  });
  return button.render();
};

var summernoteOptions = {
  toolbar: [
      ["style", ["style"]],
      ["font", ["newBold", "newItalic", "quoted", "underline", "clear"]],
      ["fontname", ["fontname"]],
      ["color", ["color"]],
      ["para", ["ul", "ol", "paragraph"]],
      ["table", ["table"]],
      ["insert", ["link", "picture", "video"]],
      ["view", ["fullscreen", "codeview", "help"]],
      ['pixabay', ['pixabay']]
  ],
  popover: {
    image: [['custom', ['imageAttributes', 'captionIt']], ["imagesize", ["imageSize100", "imageSize50", "imageSize25"]], ["float", ["floatLeft", "floatRight", "floatNone"]], ["remove", ["removeMedia"]]],
    link: [["link", ["linkDialogShow", "unlink"]]],
    air: [["color", ["color"]], ["font", ["bold", "underline", "clear"]], ["para", ["ul", "paragraph"]], ["table", ["table"]], ["insert", ["link", "picture"]]]
  },
  height: height,
  buttons: {
      newBold: BoldButton,
      newItalic: ItalicButton,
      quoted: QuoteButton,
      pixabay: PixabayButton,
  },
  imageAttributes: {
    icon: '<i class="note-icon-pencil"/>',
    removeEmpty: false,
    // true = remove attributes | false = leave empty if present
    disableUpload: false // true = don't display Upload Options | Display Upload Options

  },
  captionIt: {
    icon: '<i class="fa fa-tag">' + translations.showCaption + '</i>',
    figureClass: 'figure',
    figcaptionClass: 'wp-caption-text',
    captionText: translations.captionText
  },
  callbacks: {
    onImageUpload: function onImageUpload(files) {
      uploadImage(files[0], function (response) {
        var summernote = $('.summernote');
        var img = $('<img>').attr('src', response.url);
        summernote.summernote('insertNode', img[0]);
      });
    },
    onPaste: function onPaste(e) {
      e.preventDefault();
      var clipboardData = (e.originalEvent || e).clipboardData || window.clipboardData;
      var html = clipboardData.getData("text/html");
      html = html.replace(/<!--(.|\s)*?-->/g, "");
      html = html.replace(/(<font.*?>|<\/font>)|(<span.*?>|<\/span>)|(<div.*?>|<\/div>)|(<o:p><\/o:p>)/g, "");
      html = html.replace(/\r\n|\r|\n/g, " ");
      if(html !== ""){
          var content = $("<div>" + html + "</div>");
          content.find('*').each(function (index, element) {
              if(element.nodeName === "STYLE" || element.nodeName === "META" || element.nodeName === "TITLE" || element.nodeName === "LINK"){
                  $(element).remove();
              }
              if(element.nodeName === "IMG" || element.nodeName === "A"){
                  element.removeAttribute('style');
                  element.removeAttribute('class');
              }else {
                  while (element.attributes.length > 0) {
                      element.removeAttribute(element.attributes[0].name);
                  }
              }
          });
          document.execCommand("insertHTML", false, content[0].innerHTML);
      }else{
          var text = clipboardData.getData("Text");
          text = text.replace(/<!--(.|\s)*?-->/g, "");
          text = text.replace(/(<font.*?>|<\/font>)|(<span.*?>|<\/span>)|(<div.*?>|<\/div>)|(<o:p><\/o:p>)/g, "");
          text = text.replace(/\r\n|\r|\n/g, " ");
          document.execCommand("insertHTML", false, text);
      }
    },
  }
};
var summernoteEditor = $('.summernote').summernote(summernoteOptions);

function getEditorContent(){
    return summernoteEditor.summernote('code');
}