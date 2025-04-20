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
namespace App\FakeApi\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/fakeapi")]
class SecurityController extends AbstractController
{
    #[Route("/login", methods: ["POST"])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data["username"] !== "arthur.maurer") {
            return new JsonResponse([
                "status" => 401,
                "error" => "CONNEXION.UTILISATEUR_NON_ACCREDITE",
                "data" => null,
            ], 401);
        }

        if ($data["password"] !== "pwd") {
            return new JsonResponse([
                "status" => 401,
                "error" => "CONNEXION.IDENTIFIANTS_INVALIDES",
                "data" => null,
            ], 401);
        }

        return new JsonResponse([
            "status" => 200,
            "error" => null,
            "data" => [
                "user" => [
                    "id" => 1,
                    "username" => $data["username"],
                    "nom" => "Maurer",
                    "prenom" => "Arthur",
                    "token" => "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIiwibmFtZSI6ImFydGh1ci5tYXVyZXIiLCJpYXQiOjE1MTYyMzkwMjJ9.UlrHbPTWhITerRYdhKuPxoXkNWI5T3c53XnXpx6kCT4",
                ],
            ],
        ]);
    }

    #[Route("/logout", methods: ["POST"])]
    public function logout(): JsonResponse
    {
        return new JsonResponse([
            "status" => 200,
            "error" => null,
            "data" => null,
        ]);
    }
}
