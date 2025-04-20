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

use App\Repository\ReferentielRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait ReferentielTrait
{
    #[ORM\Column(length: 50)]
    #[Groups(["read"])]
    private ?string $code = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(["read"])]
    private ?string $mnemo = null;

    #[ORM\Column(length: 255)]
    #[Groups(["read"])]
    private ?string $libelle = null;

    public string $libelleLong;
    public string $libelleCourt;
    public string $libelleAvecMnemo;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getLibelleAvecMnemo(): string
    {
        if($this->getLibelle())
          return ucfirst($this->getLibelle()).' ('.$this->getMnemo().')';
        return "";
    }
    public function getLibelleLong(): string
    {
        return ucfirst($this->getLibelle()).' ('.$this->getCode().')';
    }

    public function getLibelleCourt(): string
    {
        return ucfirst($this->getLibelle());
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getMnemo(): ?string
    {
        return $this->mnemo;
    }

    public function setMnemo(?string $mnemo): self
    {
        $this->mnemo = $mnemo;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }
}
