<?php

class Foo
{

}

// ERROR: RuntimeException (Shopware Kernel not booted) thrown while looking for class Shopware\Models\Order\Document\Document.
class_alias(Foo::class, \Shopware\Models\Order\Document\Document::class);