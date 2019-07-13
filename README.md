### Installation
`composer require askoldex/formatter`

### Usage
```
use Askoldex\Formatter\Formatter;

class Engine
{
    public $position = [
        'x' => 1.5,
        'y' => 78,
        'z' => 56
    ];

    public function vendor()
    {
        return 'Mitsubishi Motors';
    }
}

class Car
{
    public $model = 'lancer';
    public $ls = 98;
    public $modification;
    public $color = 'green';

    public function engine()
    {
        return new Engine();
    }
}
/*
 * Init Formatter without default associations
 */
$formatter = new Formatter();

/*
 * Init Formatter with default associations
 */
$formatter = new Formatter(['car' => new Car()]);


/*
 * Associate var "car" with object Car
 */
$formatter->associate('car', new Car());
$price = 5000;
$formatter->associate('price', $price);
/*
 * If property or method always exists and has value,
 * use construction: {object.foo.bar}
 */
$text = $formatter->format('Car model: {car.model}');
echo $text.PHP_EOL; //Car model: lancer

/*
 * If property or method always exists and may have and empty value,
 * use construction: {object.foo.bar?default value}
 */
$text = $formatter->format('Car modification: {car.modification?default value}');
echo $text.PHP_EOL; //Car modification: default value

/*
 * If property or method always exists and may have an empty value,
 * and if you want to use object as default value,
 * use construction: {object.foo.bar?+object.bar}
 *
 */
$text = $formatter->format('Car ls: {car.modification?+car.ls}');
echo $text.PHP_EOL; //Car ls: 98

/*
 * If property or method may not exists and may have an empty value,
 * use construction: {object.wrong.property.or.method|default value}
 *
 */
$text = $formatter->format('Car model: {car.wrong|field is empty}');
echo $text.PHP_EOL; // Car model: field is empty

/*
 * If property or method may not exists and may have an empty value,
 * and if you want to use object as default value,
 * use construction: {object.wrong.property.or.method|+object.field}
 *
 */
$text = $formatter->format('Car color: {car.wrong|+car.color}');
echo $text.PHP_EOL; // Car color: green

/*
 * More examples
 */

$text = $formatter->format("
Engine position:
x: {car.engine.position.x}
y: {car.engine.position.y}
z: {car.engine.position.z}
Engine vendor: {car.engine.vendor}
");
echo $text.PHP_EOL;

$text = $formatter->format('Price: {price}$');
echo $text.PHP_EOL;
```