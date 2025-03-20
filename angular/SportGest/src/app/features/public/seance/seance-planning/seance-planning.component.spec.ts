import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SeancePlanningComponent } from './seance-planning.component';

describe('SeancePlanningComponent', () => {
  let component: SeancePlanningComponent;
  let fixture: ComponentFixture<SeancePlanningComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SeancePlanningComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SeancePlanningComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
