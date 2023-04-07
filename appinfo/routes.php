<?php

return [
	'routes' => [
		['name' => 'hedge#post', 'url' => '/hedge/post', 'verb' => 'POST'],
		['name' => 'edit#get', 'url' => '/edit/get', 'verb' => 'GET'],
		['name' => 'edit#create', 'url' => '/edit/create', 'verb' => 'POST'],
		['name' => 'edit#createapi', 'url' => '/edit/createapi', 'verb' => 'POST'],
		['name' => 'settings#post', 'url' => '/settings/post', 'verb' => 'POST'],
		['name' => 'settings#get', 'url' => '/settings/get', 'verb' => 'GET'],
		['name' => 'oauth#authorize', 'url' => '/oauth/authorize', 'verb' => 'GET'],
		['name' => 'oauth#userdataget', 'url' => '/oauth/userdataget', 'verb' => 'GET'],
		['name' => 'oauth#refreshtokenget', 'url' => '/oauth/refreshtokenget', 'verb' => 'POST'],
		['name' => 'oauth#tokenget', 'url' => '/oauth/tokenget', 'verb' => 'POST']
		
	]
];
