<?php
/* Для начала нам необходимо инициализировать данные, необходимые для составления запроса. */
$subdomain = 'dbistest3'; #Наш аккаунт - поддомен
#Формируем ссылку для запроса
$getAuth = '?USER_LOGIN=dbistest2@test.com&USER_HASH=6acf309ffaa8da6e7171601a260083c672293471';
$leadsLink = 'https://' . $subdomain . '.amocrm.ru/api/v2/leads' . $getAuth;
$contactsLink = 'https://' . $subdomain . '.amocrm.ru/api/v2/contacts' . $getAuth;

/**
 * Функция GetDataList - получает данные по переданной в качестве аргумента ссылке, возвращает многомерный массив с данными, обрабатывает ошибки от сервера
 *
 * @param $link
 * @return array
 */
function GetDataList($link)
{
/* Нам необходимо инициировать запрос к серверу. Воспользуемся библиотекой cURL (поставляется в составе PHP). Подробнее о
работе с этой
библиотекой Вы можете прочитать в мануале. */
    $curl = curl_init(); #Сохраняем дескриптор сеанса cURL
    #Устанавливаем необходимые опции для сеанса cURL
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
    curl_setopt($curl, CURLOPT_URL, $link);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    $out = curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
/* Вы также можете передать дополнительный HTTP-заголовок IF-MODIFIED-SINCE, в котором указывается дата в формате D, d M Y
H:i:s. При
передаче этого заголовка будут возвращены контакты, изменённые позже этой даты. */
//curl_setopt($curl, CURLOPT_HTTPHEADER, array('IF-MODIFIED-SINCE: Mon, 01 Aug 2013 07:07:23'));
    /* Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
    $code = (int) $code;
    $errors = array(
        301 => 'Moved permanently',
        400 => 'Bad request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        500 => 'Internal server error',
        502 => 'Bad gateway',
        503 => 'Service unavailable',
    );
    try
    {
        #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
        if ($code != 200 && $code != 204) {
            throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error', $code);
        }

    } catch (Exception $E) {
        die('Ошибка: ' . $E->getMessage() . PHP_EOL . 'Код ошибки: ' . $E->getCode());
    }
    /*
    Данные получаем в формате JSON, поэтому, для получения читаемых данных,
    нам придётся перевести ответ в формат, понятный PHP
     */
    $dataList = json_decode($out, true);
    return $dataList['_embedded']['items'];
}
// Получение данных по сделкам и контактам
$leadsResponse = GetDataList($leadsLink);
$contactsResponse = GetDataList($contactsLink);

// Перебор полученных массивов с данными и вывод нужных значений на экран
for ($i = 0; $i < count($leadsResponse); $i++) {
    if ($leadsResponse[$i]['company'] != null || $leadsResponse[$i]['contacts'] != null) {
        echo "Сделка: " . $leadsResponse[$i]['name'] . "<br>";
        if (isset($leadsResponse[$i]['contacts']['id'])) {
            echo "Контакты: ";
            foreach ($leadsResponse[$i]['contacts']['id'] as $value) {
                for ($j = 0; $j < count($contactsResponse); $j++) {
                    if ($contactsResponse[$j]['id'] == $value) {
                        echo $contactsResponse[$j]['name'] . "<br>";
                    }
                }
            }
        }
        if (isset($leadsResponse[$i]['company']['name'])) {
            echo "Компания: " . $leadsResponse[$i]['company']['name'] . "<br>";
        }
        echo "<br>";
    }
}
