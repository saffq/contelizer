<?php


namespace App\Controller;

use App\Form\FileUploadType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class FileController extends AbstractController
{
    /**
     * @Route("/upload", name="file_upload")
     */
    public function uploadFile(Request $request)
    {
        $form = $this->createForm(FileUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $originalContent = file_get_contents($file->getPathname());
            $transformedContent = $this->transformText($originalContent);
            $newFileName = 'transformed_' . $file->getClientOriginalName();
            $publicDirectory = $this->getParameter('kernel.project_dir') . '/public/Uploads/';
            $newFilePath = $publicDirectory . '/' . $newFileName;
            file_put_contents($newFilePath, $transformedContent);

            return $this->redirectToRoute('file_upload_success', ['newFileName' => $newFileName]);
        }

        return $this->render('file/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function transformText(string $content): string
    {
        $lines = explode(PHP_EOL, $content);
        $transformedLines = [];

        foreach ($lines as $line) {
            $words = preg_split('/\s+/', $line);
            $transformedWords = [];

            foreach ($words as $word) {
                if (mb_strlen($word, 'UTF-8') <= 2) {
                    $transformedWords[] = $word;
                } else {
                    preg_match('/^([[:punct:]]*)(.*?)([[:punct:]]*)$/', $word, $matches);
                    $punctuationBefore = $matches[1];
                    $middlePart = $matches[2];
                    $punctuationAfter = $matches[3];

                    $firstLetter = mb_substr($middlePart, 0, 1, 'UTF-8');
                    $lastLetter = mb_substr($middlePart, -1, 1, 'UTF-8');
                    $middlePart = mb_substr($middlePart, 1, -1, 'UTF-8');

                    $shuffledMiddlePart = str_shuffle($middlePart);

                    $transformedWord = $punctuationBefore . $firstLetter . $shuffledMiddlePart . $lastLetter . $punctuationAfter;
                    $transformedWords[] = $transformedWord;
                }
            }

            $transformedLines[] = implode(' ', $transformedWords);
        }

        return implode(PHP_EOL, $transformedLines);
    }

    /**
     * @Route("/upload/success/{newFileName}", name="file_upload_success")
     */
    public function uploadSuccess(string $newFileName)
    {
        return $this->render('file/upload_success.html.twig', [
            'newFileName' => $newFileName,
        ]);
    }
}
