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
namespace App\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Contracts\ReferentielEntityInterface;
use App\Repository\StatutPersonneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(operations: [new Get()], normalizationContext: ['groups' => ['read']])]
#[ORM\Table(schema: 'webapp', name: 'statut_personne')]
#[ORM\Entity(repositoryClass: StatutPersonneRepository::class)]
#[ORM\UniqueConstraint(columns: ['code'])]
#[ORM\UniqueConstraint(columns: ['libelle'])]
class StatutPersonne implements ReferentielEntityInterface
{
    const CODE_VICTIME = 'Victime';
    const CODE_PREVENU = 'Prévenu';
    const CODE_MIS_EN_CAUSE = "Mis en cause";
    const CODE_JUGE = 'Jugé';
    const CODE_PERSONNE_LIEE = "Personne liée";
    const CODE_PARTIE_CIVILE_OPPOSANT="Partie civile & opposant";
    const CODE_PARTIE_CIVILE = "Partie civile";
    const CODE_PARTIE_CIVILE_ABUSIVE="Partie civile abusive";
    const CODE_PARTIE_CIVILE_RL="Partie civile par RL";

    use ReferentielTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\Column]
    #[Groups(["read"])]
    private ?int $id = null;

    public function canMakeDecisions(): bool
    {
      return in_array($this->getCode(), [self::CODE_PREVENU, self::CODE_MIS_EN_CAUSE]);
    }

    public function __construct()
    {
        $this->affairePersonnes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
