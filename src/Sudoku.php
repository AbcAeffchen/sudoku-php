<?php
/**
 * sudoku-php
 *
 * @licence     GNU LGPL v3.0 - For details have a look at the LICENSE file
 * @copyright   2016 Alexander Schickedanz
 * @link        https://github.com/AbcAeffchen/sudoku-php
 * @author      Alexander Schickedanz (AbcAeffchen) <abcaeffchen@gmail.com>
 */
namespace AbcAeffchen\sudoku;

use InvalidArgumentException;

class Sudoku
{

    const VERY_EASY = 0;
    const EASY      = 5;
    const NORMAL    = 10;
    const MEDIUM    = 15;
    const HARD      = 20;

    private static $blockSizes = [4 => 2, 9 => 3, 16 => 4, 25 => 5, 36 => 6];
    private static $dimensions = [4, 9, 16, 25, 36];

    /**
     * Solves the Sudoku.
     *
     * @param array $sudoku
     * @param bool  $checkInput If true, the input gets checked for a valid Sudoku array, i.e.
     *                          if it's a two dimensional square array containing only int and
     *                          null values
     * @return array|false      Returns the solution or false if the sudoku is not solvable.
     * @throws InvalidArgumentException
     */
    public static function solve(array $sudoku, $checkInput = false)
    {
        if($checkInput && !self::checkInput($sudoku))
        {
            throw new InvalidArgumentException('The input is no valid Sudoku array.');
        }

        return self::recursive_solve($sudoku, count($sudoku));
    }

    private static function recursive_solve(array $sudoku, $size, $row = 0, $col = 0)
    {
        do
        {
            while( $sudoku[$row][$col] !== null )
            {
                if(!self::nextCoordinates($size,$row,$col))
                    return $sudoku;
            }

            $possibilities = self::getPossibilities($sudoku, $size, $row, $col);
            $numPos = count($possibilities);
            if($numPos === 0)
                return false;

            if($numPos === 1)
                $sudoku[$row][$col] = reset($possibilities);
            else
                break;

        } while(true);

        self::array_shuffle($possibilities);
        $nextRow = $row;
        $nextCol = $col;
        self::nextCoordinates($size,$nextRow,$nextCol);     // cannot return false here.
        foreach($possibilities as $possibility)
        {
            $sudoku[$row][$col] = $possibility;
            $res = self::recursive_solve($sudoku,$size,$nextRow,$nextCol);
            if($res !== false)
                return $res;
        }

        return false;
    }

    private static function nextCoordinates($size, &$row, &$col)
    {
        $row++;
        if( $row >= $size )
        {
            $row = 0;
            $col++;
            if( $col >= $size )
                return false;
        }

        return true;
    }

    private static function getPossibilities(array &$sudoku, $size, $row, $col)
    {
        $possibilities = range(1,$size);
        // check row and col
        for($i = 0; $i < $size; $i++)
        {
            if($sudoku[$row][$i] !== null)
                unset($possibilities[$sudoku[$row][$i] - 1]);
            if($sudoku[$i][$col] !== null)
                unset($possibilities[$sudoku[$i][$col] - 1]);
        }

        // check block
        $jumpRow = $row % self::$blockSizes[$size];
        $jumpCol = $col % self::$blockSizes[$size];
        $blockR = $row - $jumpRow;
        $blockC = $col - $jumpCol;

        for($blockRow = 0; $blockRow < self::$blockSizes[$size]; $blockRow++)
        {
            if($blockRow === $jumpRow)
                continue;

            for($blockCol = 0; $blockCol < self::$blockSizes[$size]; $blockCol++)
            {
                if($blockCol === $jumpCol || $sudoku[$blockR + $blockRow][$blockC + $blockCol] === null)
                    continue;

                unset($possibilities[$sudoku[$blockR + $blockRow][$blockC + $blockCol] - 1]);
            }
        }

        return array_values($possibilities);
    }

    /**
     * @param int      $size The size of the Sudoku. Notice: The size have to be one of the
     *                       following: 4, 9, 16, 25, 36.
     * @param int      $difficulty
     * @param int|null $seed
     * @return array|false
     * @throws InvalidArgumentException
     */
    public static function generateWithSolution($size, $difficulty, $seed = null)
    {
        // check inputs
        if(!in_array($size,self::$dimensions,true)
            || !in_array($difficulty, [self::VERY_EASY, self::EASY, self::NORMAL, self::MEDIUM, self::HARD], true)
            || ($seed !== null && !is_int($seed)) )
            throw new InvalidArgumentException('Invalid input');

        // initialize random generator
        if($seed === null)
            $seed = time();

        mt_srand($seed + $difficulty * 17);

        // select blocks to fill randomly
        $cols = range(0, self::$blockSizes[$size] - 1);
        self::array_shuffle($cols);

        // create empty sudoku
        $sudoku = array_fill(0, $size, array_fill(0, $size, null));
        $values = range(1, $size);

        // fill randomly one block in each row (of blocks)
        for($row = 0; $row < self::$blockSizes[$size]; $row += self::$blockSizes[$size])
        {
            self::array_shuffle($values);
            for($blockRows = 0; $blockRows < self::$blockSizes[$size]; $blockRows++)
            {
                for($blockCols = 0; $blockCols < self::$blockSizes[$size]; $blockCols++)
                {
                    $sudoku[$row * self::$blockSizes[$size] + $blockRows][$cols[$row] * self::$blockSizes[$size] + $blockCols] = $values[$blockRows * self::$blockSizes[$size] + $blockCols];
                }
            }
        }

        // fill the gaps
        $solution = self::solve($sudoku);
        $task = $solution;
        // make new gaps
        $numFields = pow($size, 2);
        $gapFields = range(0,$numFields - 1);
        self::array_shuffle($gapFields);

        switch($difficulty)
        {
            case self::VERY_EASY:
                $min = floor($numFields * 0.43);
                $max = ceil($numFields * 0.50);
                break;
            case self::EASY:
                $min = floor($numFields * 0.37);
                $max = ceil($numFields * 0.43);
                break;
            case self::NORMAL:
                $min = floor($numFields * 0.30);
                $max = ceil($numFields * 0.37);
                break;
            case self::MEDIUM:
                $min = floor($numFields * 0.26);
                $max = ceil($numFields * 0.30);
                break;
            case self::HARD:
//                break;
            default:
                $min = floor($numFields * 0.25);
                $max = ceil($numFields * 0.27);
        }

        $numGapFields = $numFields - mt_rand($min,$max);

        for($i = 0; $i < $numGapFields; $i++)
        {
            $row = $gapFields[$i] % $size;
            $col = ($gapFields[$i] - $row) / $size;
            $task[$row][$col] = null;
        }

        return [$task,$solution];
    }

    /**
     * Wrapper that calls Sudoku::generateWithSolution, but returns only the task.
     * @param      $size
     * @param      $difficulty
     * @param null $seed
     * @return mixed
     */
    public static function generate($size, $difficulty, $seed = null)
    {
        list($task,) = self::generateWithSolution($size, $difficulty, $seed);
        return $task;
    }

    /**
     * Checks if the input is a valid sudoku solution.
     *
     * @param array $solution The solution to be checked
     * @param array $task     The task that should be result in the solution. If provided, it
     *                        is checked if the solution relates to the task
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function checkSolution(array $solution, array $task = null)
    {
        if(!self::checkInput($solution))
        {
            throw new InvalidArgumentException('Input is no Sudoku array.');
        }

        $dim = count($solution);

        if($task !== null)
        {
            if(count($task) !== $dim)
                return false;

            for($i = 0; $i < $dim; $i++)
            {
                for($j = 0; $j < $dim; $j++)
                {
                    if($task[$i][$j] !== null && $solution[$i][$j] !== $task[$i][$j])
                        return false;
                }
            }
        }


        // check rows
        for($row = 0; $row < $dim; $row++)
        {
            $valueFound = array_fill(1,$dim,false);
            for($col = 0; $col < $dim; $col++)
            {
                // null check is only needed here
                if($solution[$row][$col] === null || $valueFound[$solution[$row][$col]] === true)
                    return false;
                else
                    $valueFound[$solution[$row][$col]] = true;
            }
        }

        // check columns
        for($col = 0; $col < $dim; $col++)
        {
            $valueFound = array_fill(1,$dim,false);
            for($row = 0; $row < $dim; $row++)
            {
                if($valueFound[$solution[$row][$col]] === true)
                    return false;
                else
                    $valueFound[$solution[$row][$col]] = true;
            }
        }

        // check blocks
        $blockSize = self::$blockSizes[$dim];
        for($row = 0; $row < $dim; $row += $blockSize)
        {
            for($col = 0; $col < $dim; $col += $blockSize)
            {
                $valueFound = array_fill(1,$dim,false);
                for($blockRow = 0; $blockRow < $blockSize;$blockRow++)
                {
                    for($blockCol = 0; $blockCol < $blockSize;$blockCol++)
                    {
                        if($valueFound[$solution[$row+$blockRow][$col+$blockCol]] === true)
                            return false;
                        else
                            $valueFound[$solution[$row+$blockRow][$col+$blockCol]] = true;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Shuffles an array using the Fisher-Yates-Algorithm and mt_rand(). So it is affected by
     * the seed set by mt_srand(). This means the result is reproducible.
     * @param array $array
     */
    private static function array_shuffle(array &$array)
    {
        for($i = count($array) - 1; $i > 0; $i--)
        {
            $j = mt_rand(0,$i);
            $temp = $array[$i];
            $array[$i] = $array[$j];
            $array[$j] = $temp;
        }
    }

    /**
     * Checks if the input is an actual sudoku. i.e. The input array has two dimensions, is
     * quadratic and contains only integers between 1 and the number of rows/columns. This
     * function also casts all non null values to int, such that a valid input containing
     * stings works fine.
     *
     * @param array $inputSudoku
     * @return bool
     */
    public static function checkInput(array &$inputSudoku)
    {
        $rowCount = count($inputSudoku);
        if(!in_array($rowCount,self::$dimensions,true))
        {
            return false;
        }

        foreach($inputSudoku as &$row)
        {
            // check dimensions
            if(!is_array($row) || count($row) !== $rowCount)
            {
                return false;
            }

            // check types
            foreach($row as &$item)
            {
                if($item === null)
                {
                    continue;
                }
                if(!is_int($item))
                {
                    $item = (int) $item;
                }
                if($item < 1 || $item > $rowCount)
                {
                    return false;
                }
            }
        }

        return true;
    }
}