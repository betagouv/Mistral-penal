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
namespace App\Form\AffaireNatinf;

use App\Form\HorodatageFait\HorodatageFaitUpdateType;
use App\Entity\Natinf;
use App\Entity\AffaireNatinf;
use App\Repository\NatinfRepository;
use App\Repository\CommuneRepository;
use Doctrine\ORM\QueryBuilder;
use PUGX\AutocompleterBundle\Form\Type\AutocompleteType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class AffaireNatinfRequalDisqualType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('id', HiddenType::class, ['mapped' => false])
        ->add('natinfId', HiddenType::class, ['mapped' => false])
        ->add('natinf', AutocompleteType::class, [
          'class' => Natinf::class,
          'label' => 'affaire_natinf.field.natinf',
          'attr' => ['class' => 'fr-input autocomplete_natinf fr-autocomplete'],
          'required' => false,
        ])
        ->add('debut', HorodatageFaitUpdateType::class)
        ->add('fin', HorodatageFaitUpdateType::class)
        ->add('qualificationDeveloppee', TextareaType::class, [
          'required' => false,
          'label' => 'affaire_natinf.field.qualification_developpee',
          'attr' => ['class' => 'fr-input'],
        ])
        ->add('communeId', HiddenType::class, ['mapped' => false])
        ->add('commune', AutocompleteType::class, [
          'class' => Commune::class,
          'label' => 'affaire_natinf.field.commune',
          'attr' => ['class' => 'fr-input autocomplete_commune fr-autocomplete'],
          'required' => false,
        ])
        ->add('lieu', TextType::class, [
          'label' => 'affaire_natinf.field.lieu',
          'attr' => ['class' => 'fr-input'],
          'required' => false,
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AffaireNatinf::class,
        ]);
    }
}
