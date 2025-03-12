export interface Booking {
  id: number;
  sessionId: number;
  userId: number;
  status: 'active' | 'completed' | 'cancelled';
  cancellationReason?: string;
  createdAt: Date;
  cancelledAt?: Date;
} 