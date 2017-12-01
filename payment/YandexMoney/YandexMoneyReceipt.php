<?php

if (!interface_exists('JsonSerializable', false)) {
    interface JsonSerializable {
        function jsonSerialize();
    }
}

/**
 * Класс чека
 */
class YandexMoneyReceipt implements JsonSerializable
{
    /** @var string Код валюты - рубли */
    const CURRENCY_RUB = 'RUB';

    /** @var string Используемая по умолчанию валюта */
    const DEFAULT_CURRENCY = self::CURRENCY_RUB;

    /** @var int Идентификатор ставки НДС по умолчанию */
    const DEFAULT_TAX_RATE_ID = 1;

    /** @var YandexMoneyReceiptItem[] Массив с информацией о покупаемых товарах */
    private $items;

    /** @var string Контакт покупателя, куда будет отправлен чек - либо имэйл, либо номер телефона */
    private $customerContact;

    /** @var int Идентификатор ставки НДС по умолчанию */
    private $taxRateId;

    /** @var string Валюта в которой производится платёж */
    private $currency;

    /** @var YandexMoneyReceiptItem|null Айтем в котором хранится информация о доставке как о товаре */
    private $shipping;

    /**
     * @param int $taxRateId
     * @param string $currency
     */
    public function __construct($taxRateId = self::DEFAULT_TAX_RATE_ID, $currency = self::DEFAULT_CURRENCY)
    {
        $this->taxRateId = $taxRateId;
        $this->items = array();
        $this->currency = $currency;
    }

    /**
     * Добавляет в чек товар
     * @param string $title Название товара
     * @param float $price Цена товара
     * @param float $quantity Количество покупаемого товара
     * @param int|null $taxId Идентификатор ставки НДС для товара или null
     * @return YandexMoneyReceipt
     */
    public function addItem($title, $price, $quantity = 1.0, $taxId = null)
    {
        if ($price <= 0 || $quantity <= 0) {
            return $this;
        }
        $this->items[] = new YandexMoneyReceiptItem($title, $quantity, $price, false, $taxId);
        return $this;
    }

    /**
     * Добавляет в чек доставку
     * @param string $title Название способа доставки
     * @param float $price Цена доставки
     * @param int|null $taxId Идентификатор ставки НДС для доставки или null
     * @return YandexMoneyReceipt
     */
    public function addShipping($title, $price, $taxId = null)
    {
        if ($price <= 0) {
            return $this;
        }
        $this->shipping = new YandexMoneyReceiptItem($title, 1.0, $price, true, $taxId);
        $this->items[] = $this->shipping;
        return $this;
    }

    /**
     * Устанавливает адрес доставки чека - или имейл или номер телефона
     * @param string $value Номер телефона или имэйл получателя
     * @return YandexMoneyReceipt
     */
    public function setCustomerContact($value)
    {
        $this->customerContact = $value;
        return $this;
    }

    /**
     * Возвращает стоимость заказа исходя из состава чека
     * @param bool $withShipping Добавить ли к стоимости заказа стоимость доставки
     * @return float Общая стоимость заказа
     */
    public function getAmount($withShipping = true)
    {
        $result = 0.0;
        foreach ($this->items as $item) {
            if ($withShipping || !$item->isShipping()) {
                $result += $item->getAmount();
            }
        }
        return $result;
    }

    /**
     * Преобразует чек в массив для дальнейшей его отправки в JSON формате
     * @return array Ассоциативный массив с чеком, готовый для отправки в JSON формате
     */
    public function jsonSerialize()
    {
        $items = array();
        if (empty($this->items)) {
            return array();
        }

        foreach ($this->items as $item) {
            if ($item->getPrice() >= 0.0) {
                $items[] = array(
                    'quantity' => (string)$item->getQuantity(),
                    'price' => array(
                        'amount' => number_format($item->getPrice(), 2, '.', ''),
                        'currency' => $this->currency,
                    ),
                    'tax' => $item->hasTaxId() ? $item->getTaxId() : $this->taxRateId,
                    'text' => $this->escapeString($item->getTitle()),
                );
            }
        }
        return array(
            'items' => $items,
            'customerContact' => $this->escapeString($this->customerContact),
        );
    }

    /**
     * Сериализует чек в JSON формат
     * @return string Чек в JSON формате
     */
    public function getJson()
    {
        if (defined('JSON_UNESCAPED_UNICODE')) {
            return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (function_exists('json_encode')) {
            // для версий PHP которые не поддерживают передачу параметров в json_encode
            // заменяем в полученной при сериализации строке уникод последовательности
            // вида \u1234 на их реальное значение в utf-8
            return preg_replace_callback(
                '/\\\\u(\w{4})/',
                array($this, 'legacyReplaceUnicodeMatches'),
                json_encode($this->jsonSerialize())
            );
        } else {
            return $this->generateJson();
        }
    }

    public function legacyReplaceUnicodeMatches($matches)
    {
        return html_entity_decode('&#x' . $matches[1] . ';', ENT_COMPAT, 'UTF-8');
    }

    /**
     * Подгоняет стоимость товаров в чеке к общей цене заказа
     * @param float $orderAmount Общая стоимость заказа
     * @param bool $withShipping Поменять ли заодно и цену доставки
     * @return YandexMoneyReceipt
     */
    public function normalize($orderAmount, $withShipping = false)
    {
        if (!$withShipping) {
            if ($this->shipping !== null) {
                if ($orderAmount > $this->shipping->getAmount()) {
                    $orderAmount -= $this->shipping->getAmount();
                } else {
                    // если сумма заказа после скидки превышает стоимость доставки, то доставку включаем в нормализацию
                    $withShipping = true;
                }
            }
        }
        $realAmount = $this->getAmount($withShipping);
        if ($realAmount != $orderAmount) {
            $coefficient = $orderAmount / $realAmount;
            $realAmount = 0.0;
            $aloneId = null;
            foreach ($this->items as $index => $item) {
                if ($withShipping || !$item->isShipping()) {
                    $item->applyDiscountCoefficient($coefficient);
                    $realAmount += $item->getAmount();
                    if ($aloneId === null && $item->getQuantity() === 1.0) {
                        $aloneId = $index;
                    }
                }
            }
            if ($aloneId === null) {
                foreach ($this->items as $index => $item) {
                    if ($withShipping || !$item->isShipping()) {
                        if ($aloneId === null && $item->getQuantity() > 1.0) {
                            $aloneId = $index;
                            break;
                        }
                    }
                }
            }
            if ($aloneId === null) {
                $aloneId = 0;
            }
            $diff = $orderAmount - $realAmount;
            if (abs($diff) >= 0.001 && isset($this->items[$aloneId])) {
                if ($this->items[$aloneId]->getQuantity() === 1.0) {
                    $this->items[$aloneId]->increasePrice($diff);
                } elseif ($this->items[$aloneId]->getQuantity() > 1.0) {
                    $item = $this->items[$aloneId]->fetchItem(1);
                    $item->increasePrice($diff);
                    array_splice($this->items, $aloneId + 1, 0, array($item));
                } else {
                    $qty = $this->items[$aloneId]->getQuantity() / 2.0;
                    $item = $this->items[$aloneId]->fetchItem($qty);
                    $item->increasePrice($diff / $qty);
                    array_splice($this->items, $aloneId + 1, 0, array($item));
                }
            }
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function notEmpty()
    {
        return !empty($this->items);
    }

    /**
     * Деэскейпирует строку для вставки в JSON
     * @param string $string Исходная строка
     * @param bool $escapeForJson
     * @return string Строка с эскейпированными "<" и ">"
     */
    private function escapeString($string, $escapeForJson = false)
    {
        if ($escapeForJson) {
            return str_replace(
                array('<', '>', '\\', '"'),
                array('&lt;', '&gt;', '\\\\', '\\"'),
                html_entity_decode($string)
            );
        }
        return str_replace(array('<', '>'), array('&lt;', '&gt;'), html_entity_decode($string));
    }

    private function generateJson()
    {
        if (empty($this->items)) {
            return '[]';
        }
        $itemsLines = array();
        foreach ($this->items as $item) {
            if ($item->getPrice() >= 0.0) {
                $itemsLines[] = '{'
                    . '"quantity":"' . $item->getQuantity() . '",'
                    . '"price":{'
                    . '"amount":"' . number_format($item->getPrice(), 2, '.', '') . '",'
                    . '"currency":"' . $this->currency . '"'
                    . '},'
                    . '"tax":' . ($item->hasTaxId() ? $item->getTaxId() : $this->taxRateId) . ','
                    . '"text":"' . $this->escapeString($item->getTitle(), true) . '"'
                    . '}';
            }
        }
        return '{"customerContact":"' . $this->escapeString($this->customerContact, true) . '",'
            . '"items":[' . implode(',', $itemsLines) . ']}';
    }
}

/**
 * Класс товара в чеке
 */
class YandexMoneyReceiptItem
{
    /** @var string Название товара */
    private $title;

    /** @var float Количество покупаемого товара */
    private $quantity;

    /** @var float Цена товара */
    private $price;

    /** @var bool Является ли наименование доставкой товара */
    private $shipping;

    /** @var int|null Идентификатор ставки НДС для конкретного товара */
    private $taxId;

    /**
     * YandexMoneyReceiptItem constructor.
     * @param string $title
     * @param float $quantity
     * @param float $price
     * @param bool $isShipping
     * @param int|null $taxId
     */
    public function __construct($title, $quantity, $price, $isShipping, $taxId)
    {
        $this->title = mb_substr($title, 0, 60, 'utf-8');
        $this->quantity = (float)$quantity;
        $this->price = round($price, 2);
        $this->shipping = $isShipping;
        $this->taxId = $taxId;
    }

    /**
     * Возвращает цену товара
     * @return float Цена товара
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Возвращает общую стоимость позиции в чеке
     * @return float Стоимость покупаемого товара
     */
    public function getAmount()
    {
        return round($this->price * $this->quantity, 2);
    }

    /**
     * Возвращает название товара
     * @return string Название товара
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Возвращает количество покупаемого товара
     * @return float Количество товара
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Проверяет, установлена ли для товара ставка НДС
     * @return bool True если ставка НДС для товара установлена, false если нет
     */
    public function hasTaxId()
    {
        return $this->taxId !== null;
    }

    /**
     * Возвращает ставку НДС товара
     * @return int|null Идентификатор ставки НДС или null если он не был установлен
     */
    public function getTaxId()
    {
        return $this->taxId;
    }

    /**
     * Привеняет для товара скидку
     * @param float $value Множитель скидки
     */
    public function applyDiscountCoefficient($value)
    {
        $this->price = round($value * $this->price, 2);
    }

    /**
     * Увеличивает цену товара на указанную величину
     * @param float $value Сумма на которую цену товара увеличиваем
     */
    public function increasePrice($value)
    {
        $this->price = round($this->price + $value, 2);
    }

    /**
     * Уменьшает количество покупаемого товара на указанное, возвращает объект позиции в чеке с уменьшаемым количеством
     * @param float $count Количество на которое уменьшаем позицию в чеке
     * @return YandexMoneyReceiptItem Новый инстанс позиции в чеке
     */
    public function fetchItem($count)
    {
        if ($count > $this->quantity) {
            throw new BadMethodCallException();
        }
        $result = new YandexMoneyReceiptItem($this->title, $count, $this->price, false, $this->taxId);
        $this->quantity -= $count;
        return $result;
    }

    /**
     * Проверяет является ли текущая позиция доставкой товара
     * @return bool True если доставка товара, false если нет
     */
    public function isShipping()
    {
        return $this->shipping;
    }
}
