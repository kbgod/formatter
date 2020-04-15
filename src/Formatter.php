<?php

namespace Askoldex\Formatter;

class Formatter
{
    private $associations = [];

    /**
     * Formatter constructor.
     * @param array $associations
     */
    public function __construct(array $associations = [])
    {
        foreach ($associations as $var => $object) {
            $this->associate($var, $object);
        }
    }

    /**
     * @param string $var
     * @param mixed $object
     */
    public function associate(string $var, $object)
    {
        $this->associations[$var] = $object;
    }

    /**
     * @param string $var
     * @return mixed|null
     */
    private function findAssoc(string $var)
    {
        if (isset($this->associations[$var])) {
            return $this->associations[$var];
        } else return null;
    }

    /**
     * @param array $arr
     * @param $data
     * @return bool|mixed|null
     */
    private function resolve(array $arr, $data)
    {
        foreach ($arr AS $val) {
            if (is_array($data)) {
                if (array_key_exists($val, $data)) {
                    $data = $data[$val] ?? null;
                    continue;
                }
            } elseif (is_object($data)) {
                if (property_exists($data, $val)) {
                    $data = $data->$val;
                    continue;
                } elseif (method_exists($data, $val)) {
                    $data = $data->$val();
                    continue;
                }
            }
            return false;
        }

        return $data;
    }

    /**
     * @param string $message
     * @return mixed
     */
    private function parseMessage(string $message)
    {
        preg_match_all("#{(.*?)}#", $message, $matches);
        return $matches[1];
    }

    /**
     * @param string $template
     * @param bool $ignore
     * @return bool|string
     */
    private function checkObject(string $template, bool $ignore)
    {
        if($ignore) return $template;
        if (mb_substr($template, 0, 1) == '+') {
            return mb_substr($template, 1);
        } else return false;
    }

    /**
     * @param string $template
     * @param bool $ignore
     * @return bool|mixed|string|null
     */
    private function getField(string $template, bool $ignore = true)
    {
        $object = $this->checkObject($template, $ignore);
        if ($object == false) return $template;
        $fields = explode('.', $object);
        if (isset($fields[0])) {
            $base = $fields[0];
            $object = $this->findAssoc($base);
            $text = $this->resolve($fields, [$base => $object]);
        } else $text = null;
        return $text;
    }

    /**
     * @param array $parts
     * @return bool|mixed|string|null
     */
    private function resolveVertical(array $parts)
    {
        if (count($parts) == 2) {
            list($template, $defaultTemplate) = $parts;
            $text = $this->getField($template);
            if ($text == null) {
                $text = $this->getField($defaultTemplate, false);
            }
            return $text;
        } else return false;
    }

    /**
     * @param array $parts
     * @return bool|mixed|string|null
     */
    private function resolveQuestion(array $parts)
    {
        if (count($parts) == 2) {
            list($template, $defaultTemplate) = $parts;
            $text = $this->getField($template);
            if ($text !== false and $text == null) {
                $text = $this->getField($defaultTemplate, false);
            }
            return $text;
        } else return false;
    }

    /**
     * @param string $template
     * @return bool|mixed|string|null
     */
    private function resolveTemplate(string $template)
    {
        $text = $this->getField($template);
        return $text;
    }

    /**
     * @param string $match
     * @param string $text
     * @param $message
     * @return bool
     */
    private function replace(string $match, string $text, &$message): bool
    {
        $message = str_replace('{' . $match . '}', $text, $message);
        return true;
    }

    private function process($match, &$message)
    {
        $text = $this->resolveVertical(explode('|', $match, 2));
        if ($text !== false) return $this->replace($match, $text, $message);

        $text = $this->resolveQuestion(explode('?', $match, 2));
        if ($text !== false) return $this->replace($match, $text, $message);

        $text = $this->resolveTemplate($match);
        if ($text !== false) return $this->replace($match, $text, $message);
    }

    /**
     * @param string $message
     * @return string
     */
    public function format(string $message = null): ?string
    {
        if($message == null) return $message;
        $matches = $this->parseMessage($message);
        foreach ($matches as $key => $match) {
            $this->process($match, $message);
        }
        return $message;
    }
}