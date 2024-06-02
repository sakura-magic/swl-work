<?php
declare(strict_types=1);

namespace work;

use work\cor\ManageVariable;
use work\traits\SingleInstance;

class Env extends ManageVariable
{
    use SingleInstance;

    /**
     * 加载env文件信息
     * @param string $path
     * @param bool $cover
     * @return bool
     */
    public function loadingFile(string $path, bool $cover = false): bool
    {
        if (!is_file($path)) {
            return false;
        }
        $envInfo = parse_ini_file($path, true);
        if ($envInfo === false) {
            return false;
        }
        foreach ($envInfo as $key => $item) {
            if (!$cover && $this->has($key)) {
                continue;
            }
            $this->set($key, $item);
        }
        return true;
    }

}