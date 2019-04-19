<?php

namespace lx;

class User extends \lx\Model {
	public function __construct($params = []) {
		parent::__construct();

		// $this->isGuest = true;
	}

	public function isGuest() {
		return $this->data === null;
	}



	// public function getRole() {
	// 	return 'admin';
	// }




	/*
	у пользователя есть роль
	за ролью закреплен список доступных прав
	роль может включать в себя другие роли, н-р [[admin]] включает права [[user]]

	при этом индивидуально пользователю можно перенастроить отдельные права
		- закрыть открытые для типа по умолчанию
		- открыть закрытые для типа по умолчанию

	роль влияет на доступ к роутам (при использовании опции [[for]])

	право влияет на доступ к роутам (при использовании опции [[right]])
	право влияет на исполнение метода респондента, настраивается в респонденте
	право влияет на исполнение экшена (класса), настраивается в экшене
	право влияет на исполнение экшена контроллера, настраивается в контроллере


	! как добавить права специфичные для конкретного сервиса
	имя права будет выглядеть как %имя/сервиса%.%имя_права%



	Нужны модули для управления:
	- списком прав
	- деревом ролей
	- назначением ролей юзерам
	- управление индивидуально юзером - закрытие, открытие ему прав вне его роли
	- мониторинг внутренних прав подключаемых сервисов


	https://habr.com/ru/company/mailru/blog/343288/
	https://habr.com/ru/company/dataart/blog/262817/

	https://habr.com/ru/company/custis/blog/248649/
	https://habr.com/ru/company/custis/blog/258861/
	https://michaelcgood.com/expression-based-access-control/
	https://nvlpubs.nist.gov/nistpubs/SpecialPublications/NIST.SP.800-162.pdf
	http://keldysh.ru/abrau/2016/p/p17.pdf
	https://core.ac.uk/download/pdf/34650555.pdf
	http://seminar.s2s.msu.ru/files/20161115_Bukhonov_Itkes.pdf



	Про заголовки
	https://habr.com/ru/post/413205/
	*/
}
