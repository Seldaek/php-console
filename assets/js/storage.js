/**
 * Created with JetBrains PhpStorm.
 * User: thomas
 * Date: 8/20/12
 * Time: 1:56 PM
 * To change this template use File | Settings | File Templates.
 */

var storage = {
    storage: null,
    dummy: "dummy_data",
    local: "local",
    session: "session",
    isAvailable: true,
    verify: function (){
        this.setStorage();
        try{
            this.set(this.dummy,this.dummy)

            if (this.get(this.dummy) == this.dummy){
                this.isAvailable = true;
            }else{
                this.isAvailable = false;
            }
        }
        catch(e){
            this.isAvailable = false;
        }

        return  this.isAvailable
    },
    set: function (key,value){
        this.getStorage().setItem(key,value)
    },
    get: function (key){
        this.getStorage().getItem(key)
    },
    update: function(key,value){
        this.set(key,value)
    },
    remove: function(key){
        this.getStorage().removeItem(key)
    },
    getStorage: function(type){
        type = type || this.local

        if (type == this.local){
            this.setStorage(localStorage)
        }else{
            this.setStorage(sessionStorage)
        }

        return this.storage
    },

    setStorage: function(storage){
        this.storage  = storage

        return this
    }
}