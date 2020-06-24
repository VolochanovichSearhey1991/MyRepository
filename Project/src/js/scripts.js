//ф-ция проверяет корректен ли email
function isRightEmail(userEmail) {
	let exp = /.+@.+\..+/i;
	
	if (exp.test(userEmail)) {
		return true;
	}
	
	return false;
}

//ф-ция проверяет совпадает ли пароль и его подтверждение
function isEqualPass(userPass, confUserPass) {
	
	if (userPass == confUserPass) {
		return true;
	}
	
	return false
}

//ф-ция проверяет поле на пустоту
function isNotEmpty(userField) {
	let exp = /\S+/;
	
	if (exp.test(userField)) {
		return true;
	}
	
	return false;
	
}

//ф-ция применяет специальный стиль к неверно заполненным полям, и добавляет элемент span с текстом ошибки
function setErrorStyle(field, mess) {
	
	if (!field.next().is('span')) {
		let span = $("<span></span>");
		field.after(span);
	}
	
	field.addClass('errorStyle');
	field.next().text(mess);
}

//ф-ция отменяет специальный стиль к неверно заполненным полям, и удаляет элемент span с текстом ошибки
function unsetErrorStyle(field) {
	field.removeClass('errorStyle');
	
	if (field.next().is('span')) {
		field.next().remove();
	}
	
}

//ф-ция очищает текст всех переданных полей
function clearFieldsValue(...fields) {
	
	for (let field of fields) {
		field.val('');
	}
}

//ф-ция добавляет класс скрывающий форму а родительскому контейнеру 
//обработчик нажатия, чтобы ее открыть
function minimize(form) {
	form.addClass('collapse');
	form.closest('div').addClass('prompter');
	form.closest('div').click(expand);
}

////ф-ция удаляет класс скрывающий форму
function expand(event) {
	target = $(event.target);
	target.children('form').removeClass('collapse');
	target.removeClass('prompter');
}

// ф-ция прячет формы и отображает авторизованного пользователя и ссылку на отмену авторизации(вызывается при успешной авторизации)
function authorizeSuccess(user) {
	$('div').addClass('collapse');
	elem = $("<p id='login'>" + user + "<a href='#'>exit</a></p>");
	$('body').append(elem);
	$('#login a').click(exit);
}

// ф-ция проверяет все переданные поля на пустоту и меняет состояние их стилей в зависимости от результата
function fieldsEmptyCheck(...fields){ 
	let flag = true;
	
	for(let field of fields) {
		
		if (isNotEmpty(field.val())) {
			unsetErrorStyle(field);
		} else {
			setErrorStyle(field,'field is empty');
			flag = false;
		}
	
	}
	
	return flag;
}

//ф-ция передает запрос на проверку авторизован ли пользователь
function checkAuthorization() {
	let jqxhr = $.post('src/php/script.php',{'type':'checkAuthorization'});
				
		jqxhr.done(function(data){
			response = JSON.parse(data);	
			if(response.match(/^Hello.*/)) {
				authorizeSuccess(response)
			}
			
		});
				
		jqxhr.fail(function(){
			alert(jqxhr.statusText);
		});
		
}

//ф-ция передает запрос на отмену авторизации и очистку всех cookies и session
function exit(event) {
	event.preventDefault();
	let jqxhr = $.post('src/php/script.php',{'type':'exit'});
	
	jqxhr.done(function(data){
		$('div').removeClass('collapse');
		minimize($('form'));
		$('p#login').remove();
	});
				
	jqxhr.fail(function(){
		alert(jqxhr.statusText);
	});
	
}

//ф-ция передает запрос на регистрацию нового пользователя и данные полей формы
function sendToRegistration(login, pass, confPass, email, name, userForm) {
	let flag = true;
	flag = fieldsEmptyCheck(login, pass, confPass, email, name);
	
	if (flag) {
			
			if (isEqualPass(pass.val(), confPass.val())) {	
				unsetErrorStyle(pass);
				unsetErrorStyle(confPass);
			} else {
				setErrorStyle(pass,'nonequivalent password');
				setErrorStyle(confPass,'nonequivalent password');
				flag = false;
			}
		
			if (isRightEmail(email.val())) {
				unsetErrorStyle(email);
			} else {
				setErrorStyle(email,'incorrect email');
				flag = false;
			}
			
			if (flag) {
				let jqxhr = $.post('src/php/script.php',userForm.serialize() + '&type=registration');
				
				jqxhr.done(function(data){
					response = JSON.parse(data);
					
					if (response == 'success') {
						
						clearFieldsValue(login, pass, confPass, email, name);
						minimize(userForm);
					}
					
					alert(response);
				});
				
				jqxhr.fail(function(){
					alert(jqxhr.statusText);
				});
				
			}	
		
		}
}

//ф-ция передает запрос на авторизацию пользователя 
function sendToAuthorization(login, pass, userForm) {
	let flag = true;
	flag = fieldsEmptyCheck(login, pass);
	
	if (flag) {
			
				let jqxhr = $.post('src/php/script.php',userForm.serialize() + '&type=authorize');
				
				jqxhr.done(function(data){
					response = JSON.parse(data);
					if(response.match(/^Hello.*/)) {
						authorizeSuccess(response)
						clearFieldsValue(login, pass);
					} else {
						alert(response);
					}
					
					
				});
				
				jqxhr.fail(function(){
					alert(jqxhr.statusText);
				});
					
		
		}
}

//ф-ция отправляет данные на регистрацию/авторизацию (в зависимости от нажатой кнопки)
function sendUserData(event) {
	event.preventDefault();

	if (event.target.getAttribute('name') == 'toRegistration') {
		sendToRegistration(userFields.login, userFields.pass, userFields.confPass, userFields.email, userFields.name, userFields.registrationForm);
	} else if (event.target.getAttribute('name') == 'toAuthorization') {
		sendToAuthorization(userFields.authorizeLogin, userFields.authorizePass, userFields.authorizationForm);
	}
	
}

let userFields = {
	registrationForm: $('form[name=userRegForm]'),
	authorizationForm: $('form[name=userAuthorizeForm]'),
	login: $('input[name=userLogin]'),
	pass: $('input[name=userPass]'),
	confPass: $('input[name=confUserPass]'),
	email: $('input[name=userEmail]'),
	name: $('input[name=userName]'),
	authorizeLogin: $('input[name=userAuthorizeLogin]'),
	authorizePass: $('input[name=userAuthorizePass]')
}

$('input[name=toRegistration]').bind('click',sendUserData);
$('input[name=toAuthorization]').bind('click',sendUserData);
$('input[type=button]').bind('click',function(event){
	
	if ($(event.target).attr('name') == 'regCollapse') {
		clearFieldsValue(userFields.login, userFields.pass, userFields.confPass, userFields.email,  userFields.name);
		
		for(let key in userFields) {
			
			if (key != 'authorizeLogin' && key != 'authorizePass') {
				unsetErrorStyle(userFields[key]);
			}
			
		}
		
	}
	
	if ($(event.target).attr('name') == 'authorizeCollapse') {
		clearFieldsValue(userFields.authorizeLogin, userFields.authorizePass);
		
		for(let key in userFields) {
			
			if (key == 'authorizeLogin' || key == 'authorizePass') {
				unsetErrorStyle(userFields[key]);
			}
			
		}
		
	} 
	
	minimize($(event.target).closest('form'));
});
minimize($('form'));
checkAuthorization();



