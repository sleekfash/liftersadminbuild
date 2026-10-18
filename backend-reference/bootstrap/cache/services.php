<?php return array (
  'providers' => 
  array (
    0 => 'Laravel\\Sanctum\\SanctumServiceProvider',
    1 => 'Laravel\\Tinker\\TinkerServiceProvider',
    2 => 'Carbon\\Laravel\\ServiceProvider',
    3 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    4 => 'Termwind\\Laravel\\TermwindServiceProvider',
    5 => 'App\\Providers\\AppServiceProvider',
    6 => 'App\\Providers\\AuthServiceProvider',
  ),
  'eager' => 
  array (
    0 => 'Laravel\\Sanctum\\SanctumServiceProvider',
    1 => 'Carbon\\Laravel\\ServiceProvider',
    2 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    3 => 'Termwind\\Laravel\\TermwindServiceProvider',
    4 => 'App\\Providers\\AppServiceProvider',
    5 => 'App\\Providers\\AuthServiceProvider',
  ),
  'deferred' => 
  array (
    'command.tinker' => 'Laravel\\Tinker\\TinkerServiceProvider',
  ),
  'when' => 
  array (
    'Laravel\\Tinker\\TinkerServiceProvider' => 
    array (
    ),
  ),
);