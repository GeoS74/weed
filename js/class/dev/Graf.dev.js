/*Класс Graf
*
*Описание:
*	создаёт и работает с древовидной структурой данных
*
*Интерфейс:
*
*	getGraf - возвращает древовидный объект
*	each - перебирает поочерёдно все элементы объекта
*	getChildrenById - возвращает ветку с головным звеном по id
*	sortBy - сортирует граф по полю
*
*/

"use strict"

function Graf( obj, parent_id )
{
  this.graf = this.makeGraf( obj, parent_id );
  return this;
}


  /*создаёт древовидный объект из одномерного объекта (массива)
  *
  *Описание:
  *		(mixed) makeGraf( (object) obj, [ (mixed) parent_id, [ (integrer) level ]] )
  *
  *		принимает объект и 2 необязательных аргумента: id родителя и уровень вложенности
  *
  *		возвращает сформированный древовидный объект либо null
  *
  *Список аргументов:
  *		obj - объект данных
  *		parent_id - значение id родителя
  *		level - уровень вложенности
  *
  *Примечание:
  *		предполагается что объект obj содержит следующие обязательные ключи: id, parent_id
  *
  *Логика работы:
  *		после получения объекта obj начинает перебирать его
  *		если parent_id элемента объекта равен текущему значению parent_id,
  *		создаётся запись во временный объект graf, при этом создаётся ключ next
  *		в который записывается дочерний элемент (если он есть)
  *
  *		результирующий объект обязательно будет содержать следующие ключи: id, parent_id, level, next
  *		если в объекте obj имеются ещё какие-то ключи, то они будут записаны в том виде как есть (1)
  *
  *Особенность при вызове из getChildrenById()
  *		метод getChildrenById передаёт конструктору объекта не только объект, но и значение parent_id
  *		при этом объект уже содержит обязательные ключи id, parent_id, level, next
  *		отсюда следует следующее: в объекте graf значение level будет сброшено на 1, но
  *		т.к. выполнение цикла (1) перезаписывает существующие ключи со со значениями, за исключением id, parent_id (2)
  *		значение level будет перезаписано
  *		таким образом если получен объект с помощью метода getChildrenById, 
  *		то значение level будет указывать на тот уровень вложенности дочернего объекта, который был в исходном объекте
  *		также необходимо при отработке цикла (1) не перезаписывать значение по ключу next если оно существует, условие (3)
  *
  *Особенность при вызове из sortBy()
  *		==bug report==
  *		метод sortBy работает с уже сформированным графом. При этом ключи next в элементах этого графа уже существуют
  *		таким образом у метода sortBy получится правильно отсортировать только элементы 1-го уровня вложенности.
  *		происходит это из-за того, что цикл (1) makeGraf перезапишет существующий ключ next на то что было до сортировки
  *		т.е. получается что рекурсивный вызов makeGraf оказывается бесполезным
  *		чтобы сортировка правильно работала и граф правильно создавался, 
  *		необходимо при отработке цикла (1) не перезаписывать значение по ключу next если оно существует, условие (3)
  *
  *Примечание:
  *	  1)переменная length - служит как флаг. Если в graf что-то записывается, то значение length становится отличным от 0
  *		это означает, что функции необходимо вернуть объект graf, иначе null
  *		если этого не делать, тогда в next может быть записан пустой объект, что осложнит обработку результирующего объекта
  */
Graf.prototype.makeGraf = function( obj, parent_id, level ) {

    parent_id = parent_id || 0;
    level = ++level || 1;

    var graf = {},
        length = 0;

    for( var key in obj ) {

      var e = obj[ key ];
	  e.parent_id = e.parent_id || 0;

      if( e.parent_id == parent_id ) {

        graf[ key ] = {
		              id:        e.id,
		  			  parent_id: e.parent_id,			
					  level:     level,	   
					  next:      this.makeGraf( obj, e.id, level ),
					  };

	    for( var i in e ) { //(1)

		  if( i == 'id' || i == 'parent_id' ) continue; //(2) значения под ключами id, parent_id не перезаписываем

		  if( i == 'next' && graf[ key ].next ) continue; //(3) значение под ключом существует не перезаписывем его

		  graf[ key ][ i ] = e[i];
	    }

        length++;
      }
    }

    return length ? graf : null;
};


  /*возвращает граф
  */
Graf.prototype.getGraf = function() { return this.graf; };


  /*сортирует граф по полю field
  */
Graf.prototype.sortBy = function( field ){ 

	var arr = [];

	this.each(function(){ arr.push( this ) });

	arr.sort(function( a, b ){

	  var a = +a[ field ] || 1000,
          b = +b[ field ] || 1000;

      return ( a > b ) ? 1 : -1;
    });

	for( var i = 0, obj = {}; i < arr.length; i++ ) obj[ '+' + arr[i].id ] = arr[i];

	this.graf = this.makeGraf( obj );
	
	return this;
};


  /*обходит древовидную структуру данных (объект)
  *
  *Описание:
  *  each( function( [index, [element]] ) )
  *
  *	 возвращает текущий объект
  *
  *	 функция получает пользовательскую функцию, которая выполняется для каждого элемента объекта
  *	 при этом this получает текущий объект данных
  *
  *  Пример вызова:
  *  	следующие два вызова делают одно и то же. Выводят в консоль текущий объект (элемент) перебираемого объекта obj
  *
  *		obj.each(function( i, el ) {
  *	  	  console.log( el );
  *		}
  *
  *		obj.each(function() {
  *	  	  console.log( this );
  *		}
  *
  *		можно вызывать вот так:
  *
  *		obj
  *       .each( function(i, el){ console.log( el ) })
  *       .each( function(){ console.log( this ) });
  */
Graf.prototype.each = function( func ) {

    var index = 0;

    f.call( this.graf, func ); //первый вызов функции-обходчика f() в контексте объекта this.el

    function f( func ) { //функция-обходчик

      for( var key in this ) {

	    if( func ) func.call( this[key], index++, this[key] ); //если есть пользовательская функция, то она вызывается в контексте текущего объекта и получает индекс итерации и сам объект

	    if( this[key].next != null ) f.call( this[key].next, func ); //если есть вложенные объекты, меняет контекст исполнения функции-обходчика f() и вызывет её
      }
    }

    return this;
};


  /*возвращает ветку с головным звеном по id
  *
  *Описание:
  *		(object) getChildrenById( (mixed) id )
  *
  *		принимает id дочернего элемента шрафа, который необходимо найти
  *
  *		возвращает новый объект этого класса, созданный с использованием найденного дочернего элемента
  *
  *Список аргументов:
  *		id - значение id дочернего элемента графа
  */
Graf.prototype.getChildrenById = function( id ) {

    var children = {},
	    parent_id;

	this.each( function(i, el) {

	  if( id == el.id ) {

	    children[ '+' + el.id ] = el;

	    parent_id = el.parent_id;
	  }
	});

	return new Graf( children, parent_id );
};

//свойства не просматриваются в цикле for..in и методе Object.keys()
Object.defineProperty(Graf.prototype, 'makeGraf', {enumerable: false})
Object.defineProperty(Graf.prototype, 'getGraf', {enumerable: false})
Object.defineProperty(Graf.prototype, 'sortBy', {enumerable: false})
Object.defineProperty(Graf.prototype, 'each', {enumerable: false})
Object.defineProperty(Graf.prototype, 'getChildrenById', {enumerable: false})