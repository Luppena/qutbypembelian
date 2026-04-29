<?php

namespace App\Filament\Traits;

use Illuminate\Support\HtmlString;
use Illuminate\Contracts\Support\Htmlable;

trait HasBackButtonHeading
{
    public function getHeading(): string | Htmlable
    {
        $url = static::getResource()::getUrl('index');
        
        $title = method_exists($this, 'getTitle') && !empty($this->getTitle()) 
            ? $this->getTitle() 
            : parent::getHeading();
            
        // Clean Title in case it's an object or htmlable
        if ($title instanceof Htmlable) {
            $title = $title->toHtml();
        }
        
        return new HtmlString("
            <div class='flex items-center gap-4'>
                <a href='{$url}' class='group relative flex items-center justify-center w-9 h-9 md:w-10 md:h-10 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm text-gray-500 transition-all duration-300 hover:text-white hover:bg-gradient-to-br hover:from-[#2563eb] hover:to-[#3b82f6] hover:border-transparent hover:shadow-md hover:-translate-y-0.5' title='Kembali'>
                    <svg class='w-5 h-5 transition-transform duration-300 group-hover:-translate-x-0.5' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2.5' stroke='currentColor'>
                        <path stroke-linecap='round' stroke-linejoin='round' d='M15.75 19.5L8.25 12l7.5-7.5' />
                    </svg>
                </a>
                <span>{$title}</span>
            </div>
        ");
    }
}
