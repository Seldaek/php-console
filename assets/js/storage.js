/**
 * Created with JetBrains PhpStorm.
 * User: thomas
 * Date: 8/20/12
 * Time: 1:56 PM
 * To change this template use File | Settings | File Templates.
 */

window.TFSN = {};

TFSN.LocalStorageHelper = function() {};

TFSN.LocalStorageHelper.prototype = {
    initialize: function(localStorageKey){
        this.localStorageKey = localStorageKey;
        this.snippetsTemplate = '#snippetsTemplate';
        this.eleToAttachTemplates = '#expandable-snippets';
        this.snippetsWrapper = '#snippets-wrapper';
        this.clearSnippetsBtn = '#clearSnippets';
        this.arrayOfSnippets = [];

        this.loadSaveSnippetsListeners();
        this.checkForExistingSnippets();
    },

    getArrayOfStorage: function(){
        return (this.getLocalStorage()) ? JSON.parse(this.getLocalStorage()) : [];
    },

    getLocalStorage: function(){
        return (localStorage.getItem(this.localStorageKey)) ? localStorage.getItem(this.localStorageKey) : false;
    },

    checkSnippet: function(ele) {

        this.arrayOfSnippets = this.getArrayOfStorage();

        var elem = $(ele);

        var thisProject = elem.attr('data-project');
        var thisLabel = elem.attr('data-label');

        for(var i = 0; i < this.arrayOfSnippets.length; i++){
            if(this.arrayOfSnippets[i].snippetProject == thisProject && this.arrayOfSnippets[i].snippetLabel == thisLabel){
                editor.getSession().setValue(this.arrayOfSnippets[i].snippetCode);
            }
        }
    },

    loadSaveSnippetsListeners: function(){
        var parent = this;
        $('#save-snippet').click(function(evt){
            var snippetCode = editor.getSession().getValue();
            var snippetProject = parent.getUrlParam('site');
            var snippetLabel = prompt('Snippet Name:');

            var newSnippet = {
                'snippetCode' : snippetCode,
                'snippetProject' : snippetProject,
                'snippetLabel' : snippetLabel
            };

            parent.arrayOfSnippets = parent.getArrayOfStorage();
            parent.arrayOfSnippets.push(newSnippet);

            localStorage.setItem(parent.localStorageKey, JSON.stringify(parent.arrayOfSnippets));

            parent.successSnippetSaved();
            parent.checkForExistingSnippets();
        });
        $(this.clearSnippetsBtn).click(function(){
            localStorage.clear();
            parent.checkForExistingSnippets();

        });
    },

    successSnippetSaved : function(){
      $('#messages').html('<div id="current-message" class="alert alert-success">Snippet has been saved...</div>');
      $('#current-message').fadeOut(2000, function(){
              $(this).remove().delay(1000);
      });
    },

    getUrlParam: function(name){
        var results = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(window.location.href);
        return results[1] || 0;
    },

    checkForExistingSnippets : function(){
        this.arrayOfSnippets = this.getArrayOfStorage();

        for(var i = 0; i < this.arrayOfSnippets.length; i++){
            $(this.snippetsTemplate).tmpl(this.arrayOfSnippets[i]).appendTo(this.eleToAttachTemplates);
        }

        if(localStorage.length <= 0){
            $(this.snippetsWrapper).hide();
        } else {
            $(this.snippetsWrapper).show();
            this.loadSnippetsSliders();
        }
    },

    loadSnippetsSliders : function(){
        $('i.preview-snippet').click(function(){
            $(this).next('pre').slideToggle();
            $(this).toggleClass('icon-minus-sign');
        });
    }

};
