<?php

namespace App\Application\OutputData;

abstract class AbstractOutputData
{
    /**
     * @return array
     */
    public function toArray()
    {
        return (array)$this;
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function renderJson()
    {
        return response()->json($this->toArray());
    }

    /**
     * @param string $key
     * @return \Illuminate\Http\Response
     */
    public function renderView($key)
    {
        return view($key, (array)$this);
    }

    /**
     * @param string $key
     * @return string
     */
    public function toHtml($key)
    {
        return \View::make($key, (array)$this)->render();
    }

}
