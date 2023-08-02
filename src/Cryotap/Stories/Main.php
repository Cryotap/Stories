<?php

namespace Cryotap\Stories;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\WritableBook;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use Vecnavium\FormsUI\SimpleForm;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener {

    public function onEnable(): void {
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
}


    public function onPlayerInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getItem();

        if ($block->getVanillaName() === "Written Book") {
            // Load a random story from saved text files
            $storyFilePath = $this->getDataFolder();
            $stories = array_diff(scandir($storyFilePath), ['..', '.']);

            if (empty($stories)) {
                $player->sendMessage(TextFormat::RED . "No stories available.");
                return;
            }

            $randomStory = $stories[array_rand($stories)];
            $storyName = pathinfo($randomStory, PATHINFO_FILENAME);
            $storyContent = file_get_contents($storyFilePath . $randomStory);

            // Split the story into pages manually
            $pages = $this->splitStoryIntoPages($storyContent);

            if (empty($pages)) {
                $player->sendMessage(TextFormat::RED . "Story content is empty.");
                return;
            }

            // Display the story pages in a custom GUI
            $this->showStoryGUI($player, $storyName, $pages);
        }
    }

    private function splitStoryIntoPages(string $story): array {
    // Split the story into pages (adjust page size as needed)
    $pageSize = 2000; // Example page size
    $words = explode(" ", $story);
    $pages = [];

    $currentPage = "";
    foreach ($words as $word) {
        if (strlen($currentPage) + strlen($word) + 1 <= $pageSize) {
            // Add the word to the current page
            $currentPage .= $word . " ";
        } else {
            // Start a new page with the word
            $pages[] = trim($currentPage);
            $currentPage = $word . " ";
        }
    }

    // Add the last page
    $pages[] = trim($currentPage);

    return $pages;
}

    private function showStoryGUI(Player $player, string $storyName, array $pages, int $currentPageIndex = 0): void {
    $form = new SimpleForm(function (Player $player, $data) use ($storyName, $pages, $currentPageIndex) {
        if ($data === null) {
            return;
        }

        $pageContent = $pages[$currentPageIndex];
        $storyPageForm = new SimpleForm(function (Player $player, $data) use ($storyName, $pages, $currentPageIndex) {
            if ($data === null) {
                return;
            }

            // Handle button clicks
            if ($data === 0 && isset($pages[$currentPageIndex + 1])) {
                // Display the next page
                $this->showStoryGUI($player, $storyName, $pages, $currentPageIndex + 1);
            }
        });

        $storyPageForm->setTitle(TextFormat::DARK_RED . $storyName);
        $storyPageForm->setContent(TextFormat::RED . $pageContent);

        // Add "Next Page" button if there is a next page
        if (isset($pages[$currentPageIndex + 1])) {
            $storyPageForm->addButton("Next Page");
        }

        $player->sendForm($storyPageForm);
    });

    $form->setTitle(TextFormat::DARK_RED . $storyName);
    $form->setContent(TextFormat::RED . "Choose a page to start reading:");

    foreach ($pages as $pageIndex => $page) {
        $form->addButton(TextFormat::DARK_RED . "Page " . ($pageIndex + 1));
    }

    $player->sendForm($form);
}
}