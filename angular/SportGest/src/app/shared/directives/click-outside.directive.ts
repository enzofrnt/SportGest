import { Directive, ElementRef, Output, EventEmitter, HostListener, Input } from '@angular/core';

@Directive({
  selector: '[clickOutside]',
  standalone: true
})
export class ClickOutsideDirective {
  @Output() clickOutside = new EventEmitter<void>();
  @Input() ignoreElement: HTMLElement | null = null;

  constructor(private elementRef: ElementRef) {}

  @HostListener('document:click', ['$event.target'])
  public onClick(target: any) {
    const clickedInside = this.elementRef.nativeElement.contains(target);
    const clickedOnIgnoredElement = this.ignoreElement?.contains(target);

    if (!clickedInside && !clickedOnIgnoredElement) {
      this.clickOutside.emit();
    }
  }
}
