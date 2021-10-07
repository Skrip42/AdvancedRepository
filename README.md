# AdvancedRepository

Суть этой приблуды - предоставить возможность делать запросы с джойнами 
так же легко как просто запросы по параметрам

есть 2 метода:
- protected function buildQuery(array $params)
- public function advancedFindBy(array $params)

первый билдит квери из параметров вида:
```php
[
    'joinedEntityName.joinedEntityFiled' => 'value',
    'joinedEntityName.joinedSubEntityName.joinedSubEntityField' => 'like:value'
]
```
второй просто оборачивает его и возвращает результат запроса 
(методы разделены чтобы иметь возможность использовать buildQuery в наследниках для организации своей логики (например пагинатора))

как видно из примера выше,помимо выпобки по релейшн сушностям эта штука позволяет задать более сложные условия:
```php
[
  'fieldName' => 'value',                    // равенство
  'fieldName' => 'not::value',               // неравыенство
  'fieldName' => 'like::value',              // нестрогое соотвествие
  'fieldName' => 'notLike::value',           // нестрогое соотвествие с отрицанием
  'fieldName' => 'in::value1,value2,value3', // вхождение в масив
  'fieldName' => 'notIn::value4,value5',     // вхождение в масив с отрицанием
  'fieldName' => 'less::value',              // меньше значения
  'fieldName' => 'lessOrEq::value',          // меньше или равно значению
  'fieldName' => 'more::value',              // больше значения
  'fieldName' => 'moreOrEq::value',          // больше или равно значения
  'fieldName' => 'empty::',                  // не определено
  'fieldName' => 'notEmpty::'                // определено
]
```

В завершении замечу, что тут крайне сырой и некрасивый код, по суть это прототип из которого уже нужно лепить полноценный компилятор запросов, но я ленивая жопа и наврятле когданибудь это сделаю
