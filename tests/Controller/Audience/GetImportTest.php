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
namespace App\Tests\Securrity;
use App\DataFixtures\UtilisateurAccrediteFixture;
use App\Repository\AudienceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GetImportTest extends WebTestCase {

    protected $client;
    protected $databaseTool;

    public function setUp(): void {
        parent::setUp();

        $this->client = $this->createClient();
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
    }

    public function tearDown(): void {
        parent::tearDown();
        unset($this->databaseTool);
    }

    private function login() {
        $crawler = $this->client->request("GET","/accueil");

        $this->databaseTool->loadFixtures([UtilisateurAccrediteFixture::class]);

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        $crawler = $this->client->followRedirect();

        $form = $crawler->filter("form[name=login]")->form();

        $form['login[username]'] = "corinne.parent106";
        $form['login[password]'] = "justice1";

        $this->client->submit($form);

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        $this->assertMatchesRegularExpression(
            "/\/accueil$/",
            $this->client->getResponse()->headers->get('Location')
        );

        $this->client->followRedirect();
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testGetImport() {
        $this->login();

        $crawler = $this->client->request('GET', '/api/audiences/v1/importation?serviceId=&date_debut=2024-01-01');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $ar = $this->getContainer()->get(AudienceRepository::class);

        $audiences = $ar->findAll();

        //sur le mois de janvier 2024 il y a une audience qui dure 6 jours, qui ne compte que pour 1 audience
        $this->assertEquals(9, count($audiences));
    }

}
