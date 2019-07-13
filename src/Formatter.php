<?php

namespace Askoldex\Formatter;

class Formatter
{
    private $associations = [];

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

    private function parseMessage(string $message)
    {
        preg_match_all("#{(.*?)}#", $message, $matches);
        return $matches[1];
    }

    private function checkObject(string $template, $ignore)
    {
        if($ignore) return $template;
        if (mb_substr($template, 0, 1) == '+') {
            return mb_substr($template, 1);
        } else return false;
    }

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

    private function resolveTemplate(string $template)
    {
        $text = $this->getField($template);
        return $text;
    }

    private function replace($match, $text, &$message)
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

    public function format(string $message)
    {
        //preg_match_all("#\{([\dA-Za-z_\.]+)\|([\dA-Za-z_\.]+)\}#", $message, $matches);
        $matches = $this->parseMessage($message);
        foreach ($matches as $key => $match) {
            $this->process($match, $message);
        }
        return $message;
    }
}