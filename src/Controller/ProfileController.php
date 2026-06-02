<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'profile.edit', methods: ['GET'])]
    public function edit(): Response
    {
        return $this->render('profile/edit.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/update', name: 'profile.update', methods: ['POST'])]
    public function update(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        
        $name = $request->request->get('name');
        $email = $request->request->get('email');
        $phone = $request->request->get('phone');
        
        if ($name) $user->setName($name);
        if ($email) $user->setEmail($email);
        if ($phone !== null) $user->setPhone($phone);

        $photoFile = $request->files->get('photo');
        if ($photoFile) {
            if ($user->getPhotoUrl()) {
                $oldPath = $this->getParameter('public_directory') . '/' . $user->getPhotoUrl();
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

            $photoFile->move(
                $this->getParameter('documents_directory').'/avatars',
                $newFilename
            );

            $user->setPhotoUrl('documents/avatars/'.$newFilename);
        }

        $em->flush();

        $this->addFlash('status', 'profile-updated');
        return $this->redirectToRoute('profile.edit');
    }

    #[Route('/password', name: 'profile.password', methods: ['POST'])]
    public function updatePassword(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        $newPassword = $request->request->get('password');

        if ($newPassword) {
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $em->flush();
            $this->addFlash('success', 'Votre mot de passe a été mis à jour.');
        }

        return $this->redirectToRoute('profile.edit');
    }

    #[Route('/destroy', name: 'profile.destroy', methods: ['POST'])]
    public function destroy(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, Security $security): Response
    {
        $user = $this->getUser();
        $password = $request->request->get('password');

        if (!$passwordHasher->isPasswordValid($user, $password)) {
            $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            return $this->redirectToRoute('profile.edit');
        }

        // Logout
        $security->logout(false);

        $em->remove($user);
        $em->flush();

        $request->getSession()->invalidate();

        return $this->redirectToRoute('app_home'); // Assuming app_home exists
    }
}
