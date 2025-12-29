<?php

namespace App\Game;

class BiscaEngine
{
    public $deck = [];
    public $playerHand = [];
    public $botHand = [];
    public $trumpCard = null;
    public $tableCards = []; // ['player' => card, 'bot' => card]
    public $scores = ['player' => 0, 'bot' => 0];
    public $deckOpen = true;
    public $turn = 'player';

    // REGRAS OFICIAIS (40 Cartas)
    // Valores: Ás=11, 7=10, Rei=4, Valete=3, Dama=2, Resto=0
    const VALUES = [
        'A' => 11, '7' => 10, 'K' => 4, 'J' => 3, 'Q' => 2,
        '6'=>0, '5'=>0, '4'=>0, '3'=>0, '2'=>0
    ];

    // Hierarquia de força (do mais forte para o mais fraco)
    const RANKS = ['A', '7', 'K', 'J', 'Q', '6', '5', '4', '3', '2'];
    const SUITS = ['S', 'H', 'D', 'C']; // Espadas, Copas, Ouros, Paus

    public function __construct($deck = null)
    {
        if ($deck) {
            foreach ($deck as $key => $value) $this->$key = $value;
        } else {
            $this->initGame();
        }
    }

    private function initGame()
    {
        // 1. Criar Baralho de 40 cartas
        foreach (self::SUITS as $suit) {
            foreach (self::RANKS as $rank) {
                $this->deck[] = [
                    'code' => $rank.$suit,
                    'rank' => $rank,
                    'suit' => $suit,
                    'value' => self::VALUES[$rank]
                ];
            }
        }
        shuffle($this->deck);

        // 2. Definir Trunfo (última carta)
        $this->trumpCard = array_pop($this->deck);

        // 3. Dar cartas (3 para cada - Bisca de 3)
        for ($i = 0; $i < 3; $i++) {
            $this->playerHand[] = array_pop($this->deck);
            $this->botHand[] = array_pop($this->deck);
        }

        // Colocar trunfo no fundo do baralho (virtualmente, na array fica no início para ser o último a sair)
        array_unshift($this->deck, $this->trumpCard);
    }

    public function playerMove($cardIndex)
    {
        if ($this->turn !== 'player') return false;
        if (!isset($this->playerHand[$cardIndex])) return false;

        $card = array_splice($this->playerHand, $cardIndex, 1)[0];
        $this->tableCards['player'] = $card;

        $this->turn = 'bot';
        $this->botMove(); // Bot responde logo

        return true;
    }

    public function botMove()
    {
        // Lógica simples do Bot (conforme enunciado)
        // Se joga em 2º, tenta ganhar. Se não conseguir, joga a mais baixa.

        $botCardIndex = 0; // Default: primeira carta

        if (isset($this->tableCards['player'])) {
            // Bot é o segundo a jogar
            $opponentCard = $this->tableCards['player'];
            $bestWinIndex = -1;
            $lowestCardIndex = 0;

            // Encontrar melhor carta para ganhar ou a mais baixa para perder
            usort($this->botHand, fn($a, $b) => $a['value'] - $b['value']); // Ordena por valor para facilitar

            foreach ($this->botHand as $index => $card) {
                if ($this->beats($card, $opponentCard)) {
                    // Se esta carta ganha, usa-a (como está ordenado crescente, usa a mais fraca que ganha)
                    $bestWinIndex = $index;
                    break;
                }
            }

            // Se encontrou carta para ganhar, usa-a. Senão, usa a mais baixa (index 0 após sort)
            $botCardIndex = ($bestWinIndex !== -1) ? $bestWinIndex : 0;
        }
        else {
            // Bot é o primeiro a jogar: joga a mais baixa (não gasta trunfos)
            usort($this->botHand, fn($a, $b) => $a['value'] - $b['value']);
            $botCardIndex = 0;
        }

        // Jogar a carta
        $card = array_splice($this->botHand, $botCardIndex, 1)[0];
        $this->tableCards['bot'] = $card;

        $this->resolveTrick();
    }

    private function beats($cardA, $cardB)
    {
        $trumpSuit = $this->trumpCard['suit'];

        // 1. Quem joga trunfo contra não-trunfo ganha
        if ($cardA['suit'] === $trumpSuit && $cardB['suit'] !== $trumpSuit) return true;
        if ($cardB['suit'] === $trumpSuit && $cardA['suit'] !== $trumpSuit) return false;

        // 2. Naipes iguais: ganha valor maior ou hierarquia
        if ($cardA['suit'] === $cardB['suit']) {
            if ($cardA['value'] !== $cardB['value']) return $cardA['value'] > $cardB['value'];
            // Se valores iguais (ex: 6 e 5 valem 0), ver hierarquia no array RANKS
            return array_search($cardA['rank'], self::RANKS) < array_search($cardB['rank'], self::RANKS);
        }

        // 3. Naipes diferentes (sem trunfo): ganha quem jogou primeiro (neste caso CardB estava na mesa)
        return false;
    }

    private function resolveTrick()
    {
        $pCard = $this->tableCards['player'];
        $bCard = $this->tableCards['bot'];
        $points = $pCard['value'] + $bCard['value'];

        // Verifica quem ganha. Nota: Se o bot jogou em 2º, a função beats verifica se Bot ganha a Player.
        // Se o bot jogou em 1º, Player joga em 2º.

        $botWins = false;
        if ($this->turn === 'bot') {
            // Bot jogou em 2º (respondeu)
            $botWins = $this->beats($bCard, $pCard);
        } else {
            // Bot jogou em 1º, Player respondeu
            $botWins = !$this->beats($pCard, $bCard);
        }

        if ($botWins) {
            $this->scores['bot'] += $points;
            $winner = 'bot';
        } else {
            $this->scores['player'] += $points;
            $winner = 'player';
        }

        // Limpar mesa e pescar
        $this->tableCards = [];

        if (count($this->deck) > 0) {
            // Vencedor pesca primeiro
            if ($winner === 'player') {
                $this->playerHand[] = array_pop($this->deck);
                $this->botHand[] = array_pop($this->deck);
            } else {
                $this->botHand[] = array_pop($this->deck);
                $this->playerHand[] = array_pop($this->deck);
            }
        } else {
            $this->deckOpen = false;
        }

        $this->turn = $winner;

        // Se o bot ganhou, é a vez dele jogar a próxima carta imediatamente
        if ($winner === 'bot' && count($this->botHand) > 0) {
            $this->botMove();
        }
    }

    public function getState() {
        return [
            'playerHand' => $this->playerHand,
            'trumpCard' => $this->trumpCard,
            'deckCount' => count($this->deck), // Contagem visual
            'table' => $this->tableCards,
            'scores' => $this->scores,
            'turn' => $this->turn,
            'gameOver' => (count($this->playerHand) == 0 && count($this->deck) == 0)
        ];
    }
}
