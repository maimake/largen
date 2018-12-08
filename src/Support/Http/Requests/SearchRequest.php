<?php

namespace Maimake\Largen\Support\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Maimake\Largen\Contracts\ISearchRequest;

abstract class SearchRequest extends FormRequest implements ISearchRequest
{
    protected $defaultSort = '-id';

    protected $enumDic;

    public function getEnumDic()
    {
        return $this->enumDic;
    }


    protected $extra_filters = [];

    public function addFilter($filters)
    {
        $this->extra_filters = array_merge($this->extra_filters, $filters);
    }

    //补充默认值
    protected function getRequestDataWithDefaultValue(){
        $data = $this->all();
        // 添加默认排序
        $data['sort'] = $data['sort'] ?? $this->defaultSort;

        $sort = $this->parserSort($data['sort']);
        if (!isset($sort['id'])) {
            $data['sort'] = $this->defaultSort . '|' . $data['sort'];
        }

        return $data;
    }


    protected function parserSort($val)
    {
        $res = explode('|', $val);
        return array_reduce($res, function ($carry, $item) {
            starts_with($item, '-')
                ? $carry[str_after($item, '-')] = 'DESC'
                : $carry[$item] = 'ASC';
            return $carry;
        }, []);
    }

    //打包数据
    public function toData()
    {
        return [
            'input' => $this->getRequestDataWithDefaultValue(),
            'search' => $this->get('search'),
            'enums' => $this->getEnumDic(),
        ];
    }

    public function getFilters()
    {
        $rtn = $this->get('search');
        return is_null($rtn) ? $this->extra_filters : array_merge($rtn, $this->extra_filters);
    }

    public function getSorters()
    {
        $sort = $this->getRequestDataWithDefaultValue()['sort'];
        return $this->parserSort($sort);
    }
}
