/*Класс SystemFunctions
*
*Описание:
*	Класс SystemFunctions содержит коллекцию системных функций
*
*Интерфейс:
*	encodeSpecialChars - выполняет преобразования противоположные php функции htmlspecialchars
*	getDigit - выделяет цифры из полученного аргумента и возвращает число (integrer) положительное или отрицательное
*	getTriad - разбивает число на триады возвращает строку
*	getNumber_1C - переводит цифры в номер типа ТД00**** (где * - любое число)
*	getFormatDate - переводит миллисекунды в дату в формате YYYY-MM-DD (с ведущим 0 у месяца)
*	getMilliSeconds - перевод даты из форматированного вида YYYY-MM-DD в миллисекунлы
*	numberCheckINN - проверяет корректность ИНН, возвращает (boolean)
*
*
*/

"use strict"

function SystemFunctions() {}

SystemFunctions.prototype.prefix = function( freq ) {
  for( var i = 0, prefix = '|-'; i < freq; i++ ) prefix += '---';
  return prefix;
};


/*выполняет преобразования противоположные htmlspecialchars()
*
*  & (амперсанд)	&amp;
*  " (двойные кавычки)	&quot;, если не установлена ENT_NOQUOTES
*  ' (одинарные кавычки)	&#039; (для ENT_HTML401) или &apos; (для ENT_XML1, ENT_XHTML или ENT_HTML5), но только если установлена ENT_QUOTES
*  < (меньше)	&lt;
*  > (больше)	&gt;
*
*WARNING!!! минификатор переводит значения типа '&quot;' сразу в символ
*/
SystemFunctions.prototype.encodeSpecialChars = function( str ) {

  if( !str ) return ''; //если аргумент не передаётся возвращает пустую строку

  str = String( str ); //явно преобразует аргумент к строковому типу
  
  return str.replace( /&quot;|&#039;|&apos;|&amp;|&lt;|&gt;/gi, function( str ){

    if( str == '&quot;' ) str =  '"';
    if( str == '&#039;' ) str =  '\'';
    if( str == '&apos;' ) str =  '\'';
    if( str == '&amp;' )  str =  '&';
    if( str == '&lt;' )   str =  '<';
    if( str == '&gt;' )   str =  '>';

    return str;	
  });
};


/*выделяет цифры из строки
*возвращает integrer
*
*ВНИМАНИЕ: если в качестве аргумента передаётся число вида 74521.6156540 (в конце 0),
*          то после преобразования к строке - 0 в конце пропадёт.
*
*
*/
SystemFunctions.prototype.getDigit = function( str ) {
  if( str == +str ) return +str;

  str = String( str ); //явно преобразует аргумент к строковому типу

  //var regexp = new RegExp( /([+-])\D*|(\d+)|[\.\,]+.*/, 'g' ),   //BUG REPORT: Chrome понимает такую запись, IE - нет (ошибка в регулярном выражении)
  var regexp = new RegExp( '([+-])\\D*|(\\d+)|[\\.\\,]+.*', 'g' ), //такую запись понимает и Chrome и IE (Внимание!!! обратный слеш нужно удваивать, ограничителей в виде /.../ нет)
  
      result = [[],[]],
	  r;

  while( r = regexp.exec(str) ){
    result[0].push( r[1] );
    result[1].push( r[2] );
  }

  return Number( (result[0][0] || '') + (result[1].join('') || '') ) || 0;
};


/*разбивает число на триады
  *возвращает строку
  *
  *преобразует аргумент к строковому типу и возвращает строку разбитую на триады
  */
SystemFunctions.prototype.getTriad = function( str ) {

  str = this.getDigit( str ); //здесь значение имеет тип (integrer)

  if( str == 0 ) return '';
  else if( str < 0 ) {
    str = String( str*-1 );
	var m = '-';
  }
  else str = String( str );

  if( str.length < 4 ) return ( m||'' ) + str;

  var a = str.length % 3; //получает остаток от деления

  //деление без остатка ()
  if( !a ) return ( m||'' ) + str.match(/[\d\.]{3}/g).join(' ');

  return ( m||'' ) + str.slice( 0, a ) + ' ' + str.slice( a ).match(/[\d\.]{3}/g).join(' ');
};


/*переводит цифры в номер типа ТД00**** (где * - любое число)
*
*/
SystemFunctions.prototype.getNumber_1C = function( str ) {
  
  str = this.getDigit( str ); //здесь значение имеет тип (integrer)
  
  if( str < 0 ) str *= -1; //число становится положительным
	
  str = '0000000000' + str; //добиваем нулями в начале строки, на случай если номер меньше 5-х цифр
	
  return 'ТД0' + str.slice(-5);
};


//получение даты в формате YYYY-MM-DD (с ведущим 0 у месяца)
SystemFunctions.prototype.getFormatDate = function( ms, mode ) {

  ms = this.getDigit( ms ); //здесь значение имеет тип (integrer)

  if( !ms ) return '';

  /*bug report
  *
  *в php функция time() возвращает количество секунд прошедших с начала ...
  *в js Date.now() возвращает соличество миллисекунд
  *из-за этого разрядность числа получается разной
  *т.к. с датой работает шаблон, то принято решение приводить всё к миллисекундам
  */
  
  var date = new Date( +ms ), //значение ms иметь численный тип!!! иначе NaN. метод getDigit() и так возвращает (integrer), поэтому здесь оператор '+' для галочки :)
      month = date.getUTCMonth(),
	  day = date.getUTCDate() < 10 ? '0'+date.getUTCDate() : date.getUTCDate();

  //т.к. нумерация месяцев начинается с 0, то месяц увеличивается на 1 и добавляется ведущий 0
  //причём при проверке условия, при любом раскладе значение month будет увеличено на 1
  if( ++month < 10 ) month = '0' + month;

  
  var result = date.getUTCFullYear() +'-'+ month +'-'+ day;
  
  if( mode == 'FULL' ) result += ' ' + date.getUTCHours() + ':' + date.getUTCMinutes() + ':' + date.getSeconds();
  if( mode == 'TIME' ) result = ' ' + date.getUTCHours() + ':' + date.getUTCMinutes() + ':' + date.getSeconds();
  return result;
};


//перевод даты из форматированного вида YYYY-MM-DD в миллисекунлы
SystemFunctions.prototype.getMilliSeconds = function( format_date ) {

  if( !format_date ) return '';

  format_date.split(/\W/g).join( '-' ); //разбивает строку по разделителю и тут же склеивает

  return Date.parse( format_date + 'Z' ) || ''; //символ Z, обозначает UTC
};


/*функция проверки корректности ИНН
*Подробнее об алгоритмах проверки контрольных чисел здесь: https://ru.wikipedia.org/wiki/Контрольное_число
*
*принимает [string] или [inegrer]
*
*возвращает [string] inn в случае корректности ИНН и [boolean] false в случае если ИНН не корректен. 
*При этом в консоль выводится сообщение, содержащее контрольное число.
*
*/
SystemFunctions.prototype.numberCheckINN = function( str ) {

  //выделение цифр из аргумента
  //здесь не подходит применение метода getDigit,
  //т.к. в случае если инн имеет первый символ '0', то при преобразовании к Integer он будет отброшен
  //регулярка выделяет только цифры
  var regexp = new RegExp( '\\d+', 'g' ), //такую запись понимает и Chrome и IE (Внимание!!! обратный слеш нужно удваивать, ограничителей в виде /.../ нет)
      result = [],
	  r;

  while( r = regexp.exec(str) ){
    result.push( r[0] );
  }
  
  var str = (result.join('') || '');

  
  var coef_list = { //таблица (массив) коэффициентов для вычисления контрольного числа
    n2_12: [7, 2, 4, 10, 3, 5, 9, 4, 6, 8, null, null],
    n1_12: [3, 7, 2, 4, 10, 3, 5, 9, 4, 6, 8, null],
    n1_10: [null, null, 2, 4, 10, 3, 5, 9, 4, 6, 8, null],
  };

  
  var inn = String( str );

  switch ( inn.length ) {
	
    case 10: 
      return numberCheck_10( inn ); 
      break;
		
	case 12: 
      return numberCheck_12( inn ); 
      break;
		
	default: 
      return false;
  }
  
  //проверка 10-ти значного ИНН (WARNING!!! NINJA CODE)
  function numberCheck_10( num ) { 
  
	num = num.split('');

    var control_num = 0;
 
    for( var i = 0, sum = 0; i < num.length; i++ ) sum += num[i] * coef_list[ 'n1_10' ][i + 2];

    control_num = sum - ~~( sum / 11 ) * 11; //операция ~~ оставляет только целое число без округления

    if( control_num == 10 ) control_num = 0;

    if( num[ 9 ] != control_num ) {

	  console.log( 'Не корректный ИНН. Контрольное число n1: ' + control_num );

	  return false;
	}

	return inn;
  }


  //проверка 12-ти значного ИНН (WARNING!!! NINJA CODE)
  function numberCheck_12( num ) {

	num = num.split('');

    for( var k = 0, pos = 11; k++ < 2; ) {

	  var control_num = 0;

	  for( var i = 0, sum = 0; i < num.length; i++ ) sum += num[i] * coef_list[ 'n' + k + '_12' ][i];

      control_num = sum - ~~( sum / 11 ) * 11; //операция ~~ оставляет только целое число без округления

      if( control_num == 10 ) control_num = 0;

	  if( num[ pos-- ] != control_num ) {

	    console.log( 'Не корректный ИНН. Контрольное число' + ' n' + k + ': ' + control_num );

	    return false;
	  }

    }

	return inn;
  }
};

//date difference
/*разница дат в днях относительно текущей даты
*/
SystemFunctions.prototype.dateDifference = function( ms_start, ms_end ) {
  
  ms_start = this.getDigit( ms_start ); //здесь значение имеет тип (integrer)
  //ms_start -= 18000000; //приведение к часовому поясу +5
  ms_end = this.getDigit( ms_end ) || Date.now();
  
  if( !ms_start ) ms_start = ms_end;
  
  var d = ms_start - ms_end;
  
  d /= 1000; //секунды
  d /= 60; //минуты
  d /= 60; //часы
  d /= 24; //дни

  if( 0 < d && d < 1 ) return 0;

  return ~~d; //операция ~~ оставляет только целое число без округления
};

/*обратный отсчет
*возвращает разницу по времени относительно текущей (либо указанной даты) без учета дней
*в случае если ms_start больше текущего времени ведёт обратный отсчёт
*иначе отсчитывает время в плюс
*
*целесообразно использовать эту функцию совместно с dateDifference(), которая возвращает количество дней
*/
SystemFunctions.prototype.countDown = function( ms_start, ms_end ) {
  
  ms_start = this.getDigit( ms_start ); //здесь значение имеет тип (integrer)
  ms_end = this.getDigit( ms_end ) || Date.now();
  
  if( !ms_start ) ms_start = ms_end;
  
  var diff = ms_start - ms_end;
  //diff -= 18000000; //приведение к часовому поясу +5

  if(diff < 0 ) diff *= -1; //если разница дат меньше 0 приводить число к положительному, иначе время отсчитывается не корректно

  var date = new Date( +diff ); //значение ms иметь численный тип!!! иначе NaN. метод getDigit() и так возвращает (integrer), поэтому здесь оператор '+' для галочки :)
  
  return date.getUTCHours() + 'ч ' + date.getUTCMinutes() + 'м ';
  //return date.getUTCHours() + 'ч ' + date.getUTCMinutes() + 'м ' + date.getSeconds() + 'c';
};


//функция для укорачивания длинных строк
SystemFunctions.prototype.cutString = function( str, length ) {
  if(!str) return '';
  length = length || 25;
  if(str.length < length) return str;
  else return str.slice(0, length) + '...';
};