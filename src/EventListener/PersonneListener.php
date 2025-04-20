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
namespace App\EventListener;

use App\Entity\AffairePersonne;
use App\Entity\Personne;
use App\Repository\AffairePersonneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Personne::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Personne::class)]
class PersonneListener {

  private ?EntityManagerInterface $_em = null;
  private ?AffairePersonneRepository $_apr = null;

  public function __construct(AffairePersonneRepository $apr, EntityManagerInterface $em) {
    $this->_em = $em;
    $this->_apr = $apr;
  }

  public function getAffairePersonneRepository(): ?AffairePersonneRepository {
    return $this->_apr;
  }

  public function getEntityManager(): ?EntityManagerInterface {
    return $this->_em;
  }

  /**
   * @param Personne $p
   * @param PrePersistEventArgs $event
   * @return void
   */
  public function prePersist(Personne $personne, PrePersistEventArgs $event): void {
    /** @todo */
  }

  /**
   * @param mixed $event
   */
  public function preUpdate(Personne $personne, PreUpdateEventArgs $event): void {
    /** @todo */
  }
}
