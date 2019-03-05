<?php

Route::any('/{any}', 'ProxyController@proxy')->where('any', '.*');
