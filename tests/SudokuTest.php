<?php

require __DIR__ . '/../vendor/autoload.php';

use AbcAeffchen\sudoku\Sudoku;

/**
 * @author    Alexander Schickedanz (AbcAeffchen) <abcaeffchen@gmail.com>
 */
class SudokuTest extends PHPUnit\Framework\TestCase
{

    public function testCheckInput()
    {
        // should be valid

        $valid1 = [[1,2,3,4],
                  [3,4,1,2],
                  [2,3,null,1],
                  [4,1,2,3]];

        $this->assertTrue(Sudoku::checkInput($valid1));

        $valid2 = [['1','2','3','4'],
                   ['3','4','1','2'],
                   ['2','3','4','1'],
                   ['4','1','2','3']];

        $this->assertTrue(Sudoku::checkInput($valid2));

        foreach($valid2 as $row)
        {
            $this->assertContainsOnly('int', $row);
        }

        // should be invalid
        $invalid = [[1,2,3,4],
                    [3,4,1,2],
                    [2,5,null,1],
                    [4,1,2,3]];

        $this->assertFalse(Sudoku::checkInput($invalid));

        $invalid = [[1,2,3,4],
                    [3,4,'x',2],
                    [2,5,null,1],
                    [4,1,2,3]];

        $this->assertFalse(Sudoku::checkInput($invalid));

        $invalid = [[1,2,3,4],
                    [3,4,1.1,2],
                    [2,5,null,1],
                    [4,1,2,3]];

        $this->assertFalse(Sudoku::checkInput($invalid));

        // different sizes
        $sudokus = [];
        foreach([1,4,9,16,25,36,49] as $size)
        {
            $row = array_fill(0,$size,1);
            $sudokus[$size] = array_fill(0,$size,$row);
        }

        $this->assertFalse(Sudoku::checkInput($sudokus[1]));
        $this->assertFalse(Sudoku::checkInput($sudokus[49]));
        $this->assertTrue(Sudoku::checkInput($sudokus[4]));
        $this->assertTrue(Sudoku::checkInput($sudokus[9]));
        $this->assertTrue(Sudoku::checkInput($sudokus[16]));
        $this->assertTrue(Sudoku::checkInput($sudokus[36]));
    }

    public function testCheckSolution()
    {
        // Valid Sudokus
        $valid1 = [[1,2,3,4],
                   [3,4,1,2],
                   [2,3,4,1],
                   [4,1,2,3]];
        $this->assertTrue(Sudoku::checkSolution($valid1));

        $valid2 = [[5,3,4,6,7,8,9,1,2],
                   [6,7,2,1,9,5,3,4,8],
                   [1,9,8,3,4,2,5,6,7],
                   [8,5,9,7,6,1,4,2,3],
                   [4,2,6,8,5,3,7,9,1],
                   [7,1,3,9,2,4,8,5,6],
                   [9,6,1,5,3,7,2,8,4],
                   [2,8,7,4,1,9,6,3,5],
                   [3,4,5,2,8,6,1,7,9]];
        $this->assertTrue(Sudoku::checkSolution($valid2));

        $this->assertFalse(Sudoku::checkSolution($valid1,$valid2));
        $task1 = [[5,3,4,6,7,8,9,1,2],
                  [6,7,2,1,9,5,3,null,null],
                  [1,9,8,3,4,2,5,6,7],
                  [8,5,9,7,6,1,4,2,3],
                  [4,2,6,8,5,3,7,9,1],
                  [7,1,3,9,2,4,8,5,6],
                  [9,6,1,5,3,7,2,8,4],
                  [2,8,7,4,1,9,6,3,5],
                  [3,4,5,2,8,6,1,7,9]];
        $task2 = [[5,3,4,6,7,8,9,1,2],
                   [6,7,2,1,9,5,3,null,null],
                   [1,9,8,3,4,2,5,6,7],
                   [8,5,9,7,6,1,4,2,3],
                   [4,2,6,8,5,3,7,9,1],
                   [7,1,3,9,2,4,8,5,6],
                   [9,6,1,5,3,7,2,4,8],
                   [2,8,7,4,1,9,6,3,5],
                   [3,4,5,2,8,6,1,7,9]];

        $this->assertTrue(Sudoku::checkSolution($valid2,$task1));
        $this->assertFalse(Sudoku::checkSolution($valid2,$task2));

        // invalid Sudokus
        $invalid1 = [[1,3,2,4],
                   [3,4,1,2],
                   [2,3,4,1],
                   [4,1,2,3]];
        $this->assertFalse(Sudoku::checkSolution($invalid1));
        $invalid2 = [[1,null,3,4],
                   [3,4,1,2],
                   [2,3,4,1],
                   [4,1,2,3]];
        $this->assertFalse(Sudoku::checkSolution($invalid2));

    }

    public function testSolve()
    {

        $sudoku1 = [[1,2,3,null],
                    [3,4,null,2],
                    [2,null,4,1],
                    [null,1,2,3]];
        $solution1 = [[1,2,3,4],
                      [3,4,1,2],
                      [2,3,4,1],
                      [4,1,2,3]];
        
        $this->assertSame($solution1, Sudoku::solve($sudoku1));

        $sudoku2 = [[null,null,null,null,null,null,null,null,null],
                    [null,7,2,1,9,5,3,4,8],
                    [null,9,8,3,4,2,5,6,7],
                    [null,5,9,7,6,1,4,2,3],
                    [null,2,6,8,5,3,7,9,1],
                    [null,1,3,9,2,4,8,5,6],
                    [null,6,1,5,3,7,2,8,4],
                    [null,8,7,4,1,9,6,3,5],
                    [null,4,5,2,8,6,1,7,9]];
        $solution2 = [[5,3,4,6,7,8,9,1,2],
                      [6,7,2,1,9,5,3,4,8],
                      [1,9,8,3,4,2,5,6,7],
                      [8,5,9,7,6,1,4,2,3],
                      [4,2,6,8,5,3,7,9,1],
                      [7,1,3,9,2,4,8,5,6],
                      [9,6,1,5,3,7,2,8,4],
                      [2,8,7,4,1,9,6,3,5],
                      [3,4,5,2,8,6,1,7,9]];

        $this->assertSame($solution2, Sudoku::solve($sudoku2));

        $this->assertFalse(Sudoku::solve([[null,null,null,2],
                                           [null,4,null,null],
                                           [null,3,null,null],
                                           [null,1,null,null]]));
    }

    public function testGenerate()
    {
        foreach([Sudoku::VERY_EASY,
                 Sudoku::EASY,
                 Sudoku::NORMAL,
                 Sudoku::MEDIUM,
                 Sudoku::HARD] as $difficulty)
        {
            // $sudoku get randomly generated
            $sudoku = Sudoku::generate(9, $difficulty);

            // it is a sudoku, since:
            // - the input is valid
            $this->assertTrue(Sudoku::checkInput($sudoku));
            // - it is not a solution
            $this->assertFalse(Sudoku::checkSolution($sudoku));
            // - but it is solvable
            $this->assertNotFalse(Sudoku::solve($sudoku));
        }

        // check reproducibility
        $this->assertSame(Sudoku::generate(9, Sudoku::NORMAL, 0),
                           Sudoku::generate(9, Sudoku::NORMAL, 0));
    }


}
