<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
class OperationalLogger
{
    public function financial(string $event, array $context=[]): void { Log::channel('operations')->info("financial.$event",$context); }
    public function blocked(string $event, array $context=[]): void { Log::channel('operations')->warning("blocked.$event",$context); }
    public function security(string $event, array $context=[]): void { Log::channel('operations')->warning("security.$event",$context); }
    public function system(string $event, array $context=[]): void { Log::channel('operations')->info("system.$event",$context); }
}
