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
namespace App\Form\NoteAudience;

use App\Entity\Affaire;
use App\Entity\NoteAudience;
use App\Entity\Security\Account;
use Doctrine\ORM\EntityManagerInterface;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class NoteAudienceType extends AbstractType
{
    private ?Account $_user = null;
    private ?Affaire $_affaire = null;
    private ?EntityManagerInterface $_em = null;

    public function setUser(Account $user): self
    {
      $this->_user = $user;

      return $this;
    }

    public function getUser(): ?Account
    {
      return $this->_user;
    }

    public function setEntityManager(EntityManagerInterface $em): self
    {
        $this->_em = $em;

        return $this;
    }


    public function getEntityManager(): ?EntityManagerInterface
    {
        return $this->_em;
    }

    public function setAffaire(Affaire $affaire): self
    {
        $this->_affaire = $affaire;

        return $this;
    }

    public function getAffaire(): ?Affaire
    {
        return $this->_affaire;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->setEntityManager($options['entity_manager']);
        $this->setAffaire($options['affaire']);
        $this->setUser($options['user']);

        $builder
            ->add('note', CKEditorType::class, [
              'config' => [
                'toolbar' => 'minimal_toolbar',
                'removeButtons' => 'Subscript,Superscript',
                'disableNativeSpellChecker' => false,
                'plugins' => 'basicstyles,wysiwygarea,link,toolbar'
              ]
            ])
            ->add('affaireId', HiddenType::class, [
              'mapped' => false,
              'data' => $this->getAffaire()->getId(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NoteAudience::class,
        ]);

        $resolver->setRequired('entity_manager');
        $resolver->setRequired('affaire');
        $resolver->setRequired('user');
    }
}
