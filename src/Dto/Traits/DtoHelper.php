<?php

namespace EscolaLms\Consultations\Dto\Traits;

trait DtoHelper
{
    protected function setterByData(array $data): void
    {
        foreach ($data as $k => $v) {
            $key = preg_replace_callback('/[_|-]([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $k);
            if (method_exists($this, 'set' . $key)) {
                $this->{'set' . $key}($v);
            }
        }
    }

}
