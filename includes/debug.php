<?php

function dump($data, $objects = null) {
    $objects = is_null($objects) ? new SplObjectStorage() : $objects;

    // get a color for the different types
    $getColor = function ($data) {
        $color = is_int($data) ? '16a085' : 'c0392b';
        $color = is_bool($data) || is_null($data) ? '8e44ad' : $color;
        $color = is_float($data) ? 'e67e22' : $color;

        return $color;
    };

    $getObjectId = function (&$obj) {
        if(!is_object($obj))
            return false;
        ob_start();
        var_dump($obj); // object(foo)#INSTANCE_ID (0) { }
        preg_match('~^.+?#(\d+)~s', ob_get_clean(), $oid);
        return $oid[1];
    };

    // check for circular references
    if (is_object($data)) {
        if ($objects->contains($data)) {
            echo sprintf('<pre style="display: inline; margin: 0;"><strong>%s</strong>', gettype($data));
            echo sprintf('(<em>%s</em>) #%d </pre>', get_class($data), $getObjectId($data));
            echo '<span style="color: #2980b9; font-size: 0.8em;">*RECURSION DETECTED*</span>';
            return;
        } else {
            $objects->attach($data);
        }
    }

    // output and exit early on simple data
    if (!is_object($data) && !is_array($data)) {
        $color = $getColor($data);
        $type = str_pad(gettype($data), 9);
        $data = var_export($data, true);

        echo sprintf('<pre><span style="font-size: 0.8em;">%s</span>', $type);
        echo sprintf('<span style="white-space: pre-wrap; color: #%s;">%s</span></pre>', $color, $data);
        return;
    }

    // create the object and array headers
    $class = is_object($data) ? get_class($data) : '';
    $object_id = is_object($data) ? '#' . $getObjectId($data) : '';

    echo sprintf('<pre style="display: inline; margin: 0;"><strong>%s</strong>', gettype($data));
    echo sprintf('(<em>%s</em>) %s (%s)</pre>', $class, $object_id, count((array) $data));

    // unify the variables in objects
    if (is_object($data)) {
        $object_data = [];
        $object_reflection = new \ReflectionClass($data);
        $object_properties = $object_reflection->getProperties();

        // display the visibility of each property
        foreach ($object_properties as $property) {
            $property->setAccessible(true);

            $key  = sprintf('"%s"', $property->getName());
            $key .= $property->isPublic() ? ':public' : '';
            $key .= $property->isProtected() ? ':protected' : '';
            $key .= $property->isPrivate() ? ':private' : '';

            $object_data[$key] = $property->getValue($data);
        }

        $data = $object_data;
    }

    // normalize spacing
    $padding_key = (count($data) ? max(array_map('strlen', array_keys($data))) + 4 : 0);

    // output the results
    echo '<ul style="list-style: none; margin: 2px 0 10px 20px; padding: 0;">';
    foreach ($data as $key => $value) {
        // recursively display objects and array
        if (is_object($value) || is_array($value)) {
            echo sprintf('<li style="margin: 0;"><pre style="display: inline; margin: 0;">[%s] </pre>', $key);
            dump($value, $objects);
            echo '</li>';
            continue;
        }

        $color = $getColor($value);
        $key = str_pad(sprintf('[%s]', $key), $padding_key);
        $type = str_pad(gettype($value), 9);
        $value = var_export($value, true);

        echo sprintf('<li><pre style="margin: 2px 0 0 0">%s', $key);
        echo sprintf('<span style="color: #000; font-size: 0.8em">%s</span>', $type);
        echo sprintf('<span style="color: #%s;">%s</span></pre></li>', $color, $value);
    }
    echo '</ul>';
}
