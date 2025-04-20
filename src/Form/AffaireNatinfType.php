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
namespace App\Form;

use App\Entity\Commune;
use App\Entity\Natinf;
use App\Entity\AffaireNatinf;
use App\Repository\NatinfRepository;
use App\Repository\CommuneRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\QueryBuilder;


class AffaireNatinfType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
          ->add('idKsp')
          ->add('natinf', EntityType::class, [
            'choice_label' => 'libelle',
            'class' => Natinf::class,
            'query_builder' => function (NatinfRepository $er): QueryBuilder {
              return $er->createQueryBuilder('u')
                  ->orderBy('u.id', 'ASC');
            },
          ])
          ->add('lieu')
          ->add('commune', EntityType::class, [
            'choice_label' => 'libelle',
            'class' => Commune::class,
            'query_builder' => function (CommuneRepository $er): QueryBuilder {
              return $er->createQueryBuilder('u')
                  ->orderBy('u.id', 'ASC');
             },
          ])
          ->add('debut', HorodatageFaitType::class, [
          ])
          ->add('fin', HorodatageFaitType::class, [
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
