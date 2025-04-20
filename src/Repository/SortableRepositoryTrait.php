<?php
/**
 * MIT License
 * 
 * Copyright (c) 2025 Mistral pénal - Incubateur du Minitère de la Justice
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace App\Repository;

trait SortableRepositoryTrait
{

  public function getNextPositionBySortableGroup(string $sortableGroup, mixed $value, string $schema, string $name): ?int
  {
      $sql = "SELECT CASE WHEN MAX(a.position) IS NULL THEN 1 ELSE MAX(a.position)+1 END AS position FROM $schema.$name a WHERE a.$sortableGroup = :value";
      $conn = $this->getEntityManager()->getConnection();
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(":value", $value);
      $result = $stmt->execute();
      if(!is_bool($result))
        return ($rec = $result->fetch()) ? $rec['position'] : 1;
      else
        return ($rec = $stmt->fetch()) ? $rec['position'] : 1;
  }
}
