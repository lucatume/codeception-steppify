<?php

/**
 * Utility method for steppify task.
 *
 * Converts any TableNode found in an array to an array of associative arrays.
 *
 * @param array $args An array of arguments that should be parsed to convert TableNode
 *              to arrays.
 * @param array $iterations Passed by reference; will be set to empty array if there
 *              there are no TableNode arguments among the arguments, will be set to
 *              an array of function call arguments if found.
 */
function steppify_convertTableNodesToArrays(array $args, &$iterations = [])
{
    foreach ($args as $key => $value) {
        if (is_a($value, 'Behat\Gherkin\Node\TableNode')) {
            $rows = $value->getRows();
            $keys = array_shift($rows);
            $array_value = array_map(function (array $row) use ($keys) {
                return array_combine($keys, $row);
            }, $rows);

            $iterations = [];

            foreach ($array_value as $arr) {
                $iterations[] = array_replace($args, [$key => $arr]);
            }
        }
    }

    return $args;
}
