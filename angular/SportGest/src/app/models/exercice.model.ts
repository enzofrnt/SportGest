import { DifficulteExercice } from './enum/difficulte-excercice.enum';

export interface Exercice {
    id?: number;
    nom: string;
    description: string;
    dureeEstimee: number;
    difficulte: DifficulteExercice;
}
