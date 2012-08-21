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
        this.eleToAttachTemplates = '#snippet-container';
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

    setLocalStorage: function(){
        localStorage.setItem(this.localStorageKey, JSON.stringify(this.arrayOfSnippets));
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
        $(this.eleToAttachTemplates).empty();
        for(var i = 0; i < this.arrayOfSnippets.length; i++){
            $(this.snippetsTemplate).tmpl(this.arrayOfSnippets[i]).appendTo(this.eleToAttachTemplates);
        }

        if(this.arrayOfSnippets.length <= 0){
            $(this.snippetsWrapper).hide();
        } else {
            $(this.snippetsWrapper).show();
            this.loadSnippetsSliders();
        }
    },

    loadSnippetsSliders : function(){
        $('i.preview-snippet').click(function(){
            $(this).parent().find('pre').slideToggle();
            $(this).toggleClass('icon-minus-sign');
        });

        this.arrayOfSnippets = this.getArrayOfStorage();
        var parent = this;
        $('.remove-snippet').click(function(){
            var elem = $(this).parent().find('a');
            var thisProject = elem.attr('data-project');
            var thisLabel = elem.attr('data-label');
            for(var i = 0; i < parent.arrayOfSnippets.length; i++){
                if(parent.arrayOfSnippets[i].snippetProject == thisProject && parent.arrayOfSnippets[i].snippetLabel == thisLabel){
                    parent.arrayOfSnippets.splice(i, 1);
                    parent.setLocalStorage();
//                    localStorage.setItem(parent.localStorageKey, JSON.stringify(parent.arrayOfSnippets));
                    parent.checkForExistingSnippets();
                    break;
                }
            }
        });
    }

};
