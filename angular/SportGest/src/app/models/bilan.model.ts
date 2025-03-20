export interface Sportif {
  id: number;
  nom: string;
  prenom: string;
  niveauSportif: string;
  dateInscription: string;
}

export interface Periode {
  debut: string;
  fin: string;
}

export interface Statistiques {
  nbSeances: number;
  dureeTotal: number;
  moyenneHebdo: number;
}

export interface TypeSeance {
  type: string;
  nbSeances: number;
}

export interface Coach {
  id: number;
  nom: string;
  prenom: string;
  nbSeances: number;
  dureeTotal: number;
}

export interface Exercice {
  id: number;
  nom: string;
  description: string;
  duree: number;
  difficulte: string;
}

export interface TopExercice {
  id: number;
  nom: string;
  difficulte: string;
  nbFois: number;
  dureeTotal: number;
}

export interface Seance {
  id: number;
  dateDebut: string;
  dateFin: string;
  duree: number;
  typeSeance: string;
  theme: string | null;
  niveauSeance: string;
  coach: {
    id: number;
    nom: string;
    prenom: string;
  };
  exercices: Exercice[];
  nbExercices: number;
}

export interface BilanSportif {
  sportif: Sportif;
  periode: Periode;
  statistiques: Statistiques;
  typesSeances: TypeSeance[];
  coachs: Coach[];
  topExercices: TopExercice[];
  seances: Seance[];
} 