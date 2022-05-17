<?php

namespace uvb\Models;

use \Exception;

final class Geolocation
{
    /**
     * @var string|null Тип геометки
     */
    public ?string $Type = null;

    /**
     * @var float|null Широта
     */
    public ?float $Latitude = null;

    /**
     * @var float|null Долгота
     */
    public ?float $Longitude = null;

    /**
     * @var string|null Название страны
     */
    public ?string $Country = null;

    /**
     * @var string|null Название города
     */
    public ?string $City = null;

    /**
     * @var string|null Название места (если назначено)
     */
    public ?string $Title = null;

    /**
     * @var string|null URL иконки
     */
    public ?string $Icon = null;

    /**
     * @var int|null Дата создания (если назначено)
     */
    public ?int $Created = null;

    /**
     * @var int|null Идентификатор места (если назначено)
     */
    public ?int $PlaceId = null;

    /**
     * @var int|null Тип чекина (если место добавлено как чекин сообщества)
     */
    public ?int $CheckinType = null;

    /**
     * @var Group|null Сообщество, которому принадлежит геолокация (если место добавлено как чекин сообщества)
     */
    public ?Group $Group = null;

    /**
     * @var string|null URL миниатюры главной фотографии сообщества (если место добавлено как чекин сообщества)
     */
    public ?string $GroupPhoto = null;

    /**
     * @var int|null Количество чекинов (если место добавлено как чекин сообщества)
     */
    public ?int $Checkins = null;

    /**
     * @var int|null Дата и время последнего обновления чекина в формате Unixtime (если место добавлено как чекин сообщества)
     */
    public ?int $Updated = null;

    /**
     * @var int|null Адрес чекина (если место добавлено как чекин сообщества)
     */
    public ?int $Address = null;

    /**
     * Конвертирует массив-объект VK API геолокации в объект
     *
     * @param array<string, mixed> $sourceData Исходные данные
     * @return Geolocation Объект геолокации
     * @throws Exception Выбрасывает исключение в следующих случаях: исходные данные не содержат тип геометки; некорректные или отсутствие координат;
     */
    public static function FromVkArray(array $sourceData) : Geolocation
    {
        $object = new Geolocation;
        if (!isset($sourceData["type"]) || !is_string($sourceData["type"]))
        {
            throw new Exception("Source data doesn't contain geolocation type or it is not a string");
        }
        if
        (
            !isset($sourceData["coordinates"]) || !is_array($sourceData["coordinates"]) ||
            !isset($sourceData["coordinates"]["latitude"]) || (!is_float($sourceData["coordinates"]["latitude"]) && !is_integer($sourceData["coordinates"]["latitude"])) ||
            !isset($sourceData["coordinates"]["longitude"]) || (!is_float($sourceData["coordinates"]["longitude"]) && !is_integer($sourceData["coordinates"]["longitude"]))
        )
        {
            throw new Exception("Source data must contains coordinates object. Latitude and longitude must be float or integer");
        }

        $object->Latitude = $sourceData["coordinates"]["latitude"];
        $object->Longitude = $sourceData["coordinates"]["longitude"];

        if (isset($sourceData["place"]))
        {
            $name = "country";
            $name1 = "Country";
            if (isset($sourceData["place"][$name]) && is_string($sourceData["place"][$name]))
                $object->$name1 = $sourceData["place"][$name];

            $name = "city";
            $name1 = "City";
            if (isset($sourceData["place"][$name]) && is_string($sourceData["place"][$name]))
                $object->$name1 = $sourceData["place"][$name];

            $name = "title";
            $name1 = "Title";
            if (isset($sourceData["place"][$name]) && is_string($sourceData["place"][$name]))
                $object->$name1 = $sourceData["place"][$name];

            $name = "created";
            $name1 = "Created";
            if (isset($sourceData["place"][$name]) && is_integer($sourceData["place"][$name]))
                $object->$name1 = $sourceData["place"][$name];

            $name = "icon";
            $name1 = "Icon";
            if (isset($sourceData["place"][$name]) && is_string($sourceData["place"][$name]))
                $object->$name1 = $sourceData["place"][$name];

            $name = "group_photo";
            $name1 = "GroupPhoto";
            if (isset($sourceData["place"][$name]) && is_string($sourceData["place"][$name]))
                $object->$name1 = $sourceData["place"][$name];

            $name = "type";
            $name1 = "CheckinType";
            if (isset($sourceData["place"][$name]) && is_integer($sourceData["place"][$name]))
                $object->$name1 = $sourceData["place"][$name];

            $name = "group_id";
            $name1 = "Group";
            if (isset($sourceData["place"][$name]) && is_integer($sourceData["place"][$name]))
                $object->$name1 = Group::Get($sourceData["place"][$name]);

            $name = "checkins";
            $name1 = "Checkins";
            if (isset($sourceData["place"][$name]) && is_integer($sourceData["place"][$name]))
                $object->$name1 = $sourceData["place"][$name];

            $name = "updated";
            $name1 = "Updated";
            if (isset($sourceData["place"][$name]) && is_integer($sourceData["place"][$name]))
                $object->$name1 = $sourceData["place"][$name];

            $name = "address";
            $name1 = "address";
            if (isset($sourceData["place"][$name]) && is_integer($sourceData["place"][$name]))
                $object->$name1 = $sourceData["place"][$name];
        }
        return $object;
    }

    /**
     * Конвертирует объект в массив-объект геометки VK API
     *
     * @return array<string, mixed>
     * @throws Exception Выбрасывает исключение, если геолокация не содержит координаты или тип геометки
     */
    public function ToVkArray() : array/*<string, mixed>*/
    {
        $result = array();

        if ($this->Latitude == null || $this->Longitude == null)
        {
            throw new Exception("Object has no coordinates or has only one of them");
        }

        if ($result["type"] == null)
        {
            throw new Exception("Object has no geolocation type (Type property)");
        }

        $result["type"] = $this->Type;
        $result["coordinates"] = array
        (
            "latitude" => $this->Latitude,
            "longitude" => $this->Longitude
        );
        $result["place"] = array();
        $name = "country";
        $name1 = "Country";
        if ($this->$name1 != null)
            $result["place"][$name] = $this->$name1;

        $name = "city";
        $name1 = "City";
        if ($this->$name1 != null)
            $result["place"][$name] = $this->$name1;

        $name = "title";
        $name1 = "Title";
        if ($this->$name1 != null)
            $result["place"][$name] = $this->$name1;

        $name = "created";
        $name1 = "Created";
        if ($this->$name1 != null)
            $result["place"][$name] = $this->$name1;

        $name = "icon";
        $name1 = "Icon";
        if ($this->$name1 != null)
            $result["place"][$name] = $this->$name1;

        $name = "group_photo";
        $name1 = "GroupPhoto";
        if ($this->$name1 != null)
            $result["place"][$name] = $this->$name1;

        $name = "type";
        $name1 = "CheckinType";
        if ($this->$name1 != null)
            $result["place"][$name] = $this->$name1;

        $name = "group_id";
        $name1 = "Group";
        if ($this->$name1 != null)
            $result["place"][$name] = $this->Group->GetVkId();

        $name = "checkins";
        $name1 = "Checkins";
        if ($this->$name1 != null)
            $result["place"][$name] = $this->$name1;

        $name = "updated";
        $name1 = "Updated";
        if ($this->$name1 != null)
            $result["place"][$name] = $this->$name1;

        $name = "address";
        $name1 = "address";
        if ($this->$name1 != null)
            $result["place"][$name] = $this->$name1;

        return $result;
    }

    /**
     * Рассчитывает дистанцию между двумя геолокациями
     *
     * @param Geolocation $anotherGeolocation другая геометка
     * @return float Расстояние между двумя геометками в метрах
     */
    public function Distance(Geolocation $anotherGeolocation) : float
    {
        $latitude1 = $this->Latitude;
        $latitude2 = $anotherGeolocation->Latitude;
        $longitude1 = $this->Longitude;
        $longitude2 = $anotherGeolocation->Longitude;

        $p1 = deg2rad($latitude1);
        $p2 = deg2rad($latitude2);
        $dp = deg2rad($latitude2 - $latitude1);
        $dl = deg2rad($longitude2 - $longitude1);
        $a = (sin($dp / 2) * sin($dp / 2)) + (cos($p1) * cos($p2) * sin($dl / 2) * sin($dl / 2));
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $r = 6371008;
        return $r * $c;
    }
}