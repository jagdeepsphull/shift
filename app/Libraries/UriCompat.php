<?php

namespace App\Libraries;

/**
 * CodeIgniter 3 `$this->uri` facade.
 */
class UriCompat
{
    /**
     * CI3 `$this->uri->segment($n, $default)` - 1-based, relative to the
     * application root (see `uri_segment()` in the ci3compat helper).
     *
     * @return mixed
     */
    public function segment($n, $default = '')
    {
        return uri_segment((int) $n, $default);
    }

    /**
     * @return mixed
     */
    public function rsegment($n, $default = '')
    {
        return $this->segment($n, $default);
    }

    /**
     * @return list<string>
     */
    public function segment_array(): array
    {
        $segments = [];

        for ($i = 1; ($segment = uri_segment($i, null)) !== null; $i++) {
            $segments[] = $segment;
        }

        return $segments;
    }

    public function total_segments(): int
    {
        return count($this->segment_array());
    }

    public function uri_string(): string
    {
        return implode('/', $this->segment_array());
    }
}
