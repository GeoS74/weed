// Add semicolon to prevent IIFE from being passed as argument to concatenated code.
;

//Node.remove
(function() {
  var arr = [window.Element, window.CharacterData, window.DocumentType];
  var args = [];

  arr.forEach(function (item) {
    if (item) {
      args.push(item.prototype);
    }
  });

  (function (arr) {
    arr.forEach(function (item) {
      if (item.hasOwnProperty('remove')) {
        return;
      }
      Object.defineProperty(item, 'remove', {
        configurable: true,
        enumerable: true,
        writable: true,
        value: function remove() {
          if(this.parentNode) this.parentNode.removeChild(this);
        }
      });
    });
  })(args);
})();

//Node.closest
(function(ELEMENT) {
    ELEMENT.matches = ELEMENT.matches || ELEMENT.mozMatchesSelector || ELEMENT.msMatchesSelector || ELEMENT.oMatchesSelector || ELEMENT.webkitMatchesSelector;
    ELEMENT.closest = ELEMENT.closest || function closest(selector) {
        if (!this) return null;
        if (this.matches(selector)) return this;
        if (!this.parentElement) {return null}
        else return this.parentElement.closest(selector)
      };
}(Element.prototype));

//Node.after
(function(x){
 var o=x.prototype,p='after';
 if(!o[p]){
    o[p]=function(){
     var e, m=arguments, l=m.length, i=0, t=this, p=t.parentNode, n=Node, s=String, d=document;
     if(p!==null){
        while(i<l){
         e=m[i];
         if(e instanceof n){
            t=t.nextSibling;
            if(t!==null){
                p.insertBefore(e,t);
            }else{
                p.appendChild(e);
            };
         }else{
            p.appendChild(d.createTextNode(s(e)));
         };
         ++i;
        };
     };
    };
 };
})(Element);

//Node.append
(function (arr) {
  arr.forEach(function (item) {
    if (item.hasOwnProperty('append')) {
      return;
    }
    Object.defineProperty(item, 'append', {
      configurable: true,
      enumerable: true,
      writable: true,
      value: function append() {
        var argArr = Array.prototype.slice.call(arguments),
          docFrag = document.createDocumentFragment();

        argArr.forEach(function (argItem) {
          var isNode = argItem instanceof Node;
          docFrag.appendChild(isNode ? argItem : document.createTextNode(String(argItem)));
        });

        this.appendChild(docFrag);
      }
    });
  });
})([Element.prototype, Document.prototype, DocumentFragment.prototype]);