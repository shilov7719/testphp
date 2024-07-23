<?php

namespace NamePlugin;

class NameApi
{
  public $api_url;

  public function __construct($api_url)
  {
    $this->api_url = $api_url;
  }
  // Используем тут do-while , удаляаем метку 
  public function list_vacancies($post, $vid = 0)
  {
    global $wpdb;

    $ret = array();

    if (!is_object($post)) {
      return false;
    }

    $page = 0;
    $found = false;

    do {
      $params = "status=all&id_user=" . $this->self_get_option('superjob_user_id') . "&with_new_response=0&order_field=date&order_direction=desc&page={$page}&count=100";
      $res = $this->api_send($this->api_url . '/hr/vacancies/?' . $params);
      $res_o = json_decode($res);

      if ($res !== false && is_object($res_o) && isset($res_o->objects)) {
        $ret = array_merge($res_o->objects, $ret);

        if ($vid > 0) {
          foreach ($res_o->objects as $key => $value) {
            if ($value->id == $vid) {
              $found = $value;
              break;
            }
          }
        }
        // Проверка на существование значение свойства more в объекте ответа API
        if ($found === false && isset($res_o->more) && $res_o->more) {
          $page++;
        } else {
          break;
        }
      } else {
        return false;
      }
    } while (true);

    if (is_object($found)) {
      return $found;
    } else {
      return $ret;
    }
  }
  // метод для отправки запроса 
  public function api_send($url)
  {
    // Отправка запроса к API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
      curl_close($ch);
      return false;
    }

    curl_close($ch);

    if ($http_code !== 200) {
      return false;
    }

    return $response;
  }

  public function self_get_option($option_name)
  {
    return get_option($option_name);
  }
}

$api = new \NamePlugin\NameApi('https://api.name.com'); //подставляем нужный api 
$post_id = 123;
$post = get_post($post_id);
$vacancies = $api->list_vacancies($post);

if ($vacancies !== false) {
  // Обработка  вакансий
  var_dump($vacancies);
} else {
  echo "Не удалось получить вакансии.";
}
