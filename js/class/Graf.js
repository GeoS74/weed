"use strict"
function Graf(obj,parent_id){this.graf=this.makeGraf(obj,parent_id);return this}
Graf.prototype.makeGraf=function(obj,parent_id,level){parent_id=parent_id||0;level=++level||1;var graf={},length=0;for(var key in obj){var e=obj[key];e.parent_id=e.parent_id||0;if(e.parent_id==parent_id){graf[key]={id:e.id,parent_id:e.parent_id,level:level,next:this.makeGraf(obj,e.id,level),};for(var i in e){if(i=='id'||i=='parent_id')continue;if(i=='next'&&graf[key].next)continue;graf[key][i]=e[i]}
length++}}
return length?graf:null};Graf.prototype.getGraf=function(){return this.graf};Graf.prototype.sortBy=function(field){var arr=[];this.each(function(){arr.push(this)});arr.sort(function(a,b){var a=+a[field]||1000,b=+b[field]||1000;return(a>b)?1:-1});for(var i=0,obj={};i<arr.length;i++)obj['+'+arr[i].id]=arr[i];this.graf=this.makeGraf(obj);return this};Graf.prototype.each=function(func){var index=0;f.call(this.graf,func);function f(func){for(var key in this){if(func)func.call(this[key],index++,this[key]);if(this[key].next!=null)f.call(this[key].next,func)}}
return this};Graf.prototype.getChildrenById=function(id){var children={},parent_id;this.each(function(i,el){if(id==el.id){children['+'+el.id]=el;parent_id=el.parent_id}});return new Graf(children,parent_id)};Object.defineProperty(Graf.prototype,'makeGraf',{enumerable:!1})
Object.defineProperty(Graf.prototype,'getGraf',{enumerable:!1})
Object.defineProperty(Graf.prototype,'sortBy',{enumerable:!1})
Object.defineProperty(Graf.prototype,'each',{enumerable:!1})
Object.defineProperty(Graf.prototype,'getChildrenById',{enumerable:!1})